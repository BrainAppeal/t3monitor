<?php
/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2021 Brain Appeal GmbH (info@brain-appeal.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

namespace BrainAppeal\T3monitor\CoreApi\Common\Reports;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Reports for install tool.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class InstallTool extends AbstractReport
{
    /**
     * Returns information about the database tables
     *
     * @param Reports $reportHandler
     */
    public function addReports(Reports $reportHandler)
    {
        $info = [
            'database' => $this->getDatabaseSchemaUpdates(),
            'wizardStates' => $this->getUpdateWizardStates(),
        ];
        $reportHandler->add('install_tool', $info);
    }

    /**
     * Returns information about necessary schema updates
     */
    protected function getDatabaseSchemaUpdates()
    {
        if (!class_exists(\TYPO3\CMS\Core\Database\Schema\SchemaMigrator::class)) {
            return [];
        }
        /** @var \TYPO3\CMS\Core\Database\Schema\SqlReader $sqlReader */
        $sqlReader = $this->coreApi->makeInstance(\TYPO3\CMS\Core\Database\Schema\SqlReader::class);
        /** @var \TYPO3\CMS\Core\Database\Schema\SchemaMigrator $schemaMigrator */
        $schemaMigrator = $this->coreApi->makeInstance(\TYPO3\CMS\Core\Database\Schema\SchemaMigrator::class);
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $schemaUpdates = [];
        try {
            $addCreateChange = $schemaMigrator->getUpdateSuggestions($sqlStatements);
            // Aggregate the per-connection statements into one flat array
            $schemaUpdates['add'] = array_merge_recursive(...array_values($addCreateChange));
        } catch (\Throwable $e) {
            unset($e);
        }

        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        // Difference from current to expected
        try {
            $dropRename = $schemaMigrator->getUpdateSuggestions($sqlStatements, true);
            // Aggregate the per-connection statements into one flat array
            $schemaUpdates['remove'] = array_merge_recursive(...array_values($dropRename));
        } catch (\Throwable $e) {
            unset($e);
        }

        return $this->fixArrayXmlKeyNames($schemaUpdates);
    }

    /**
     * Replace md5 keys from array (array will be converted to XML later and XML tag names must start with letter or underscore)
     *
     * @param array $data
     * @return array
     */
    private function fixArrayXmlKeyNames($data) {
        if (is_array($data)) {
            $keys = array_keys($data);
            foreach ($keys as $key) {
                if (is_array($data[$key])) {
                    $data[$key] = $this->fixArrayXmlKeyNames($data[$key]);
                }
                if (preg_match('/^(\d+).*/', $key, $matches)) {
                    $newKey = 'a' . $key;
                    if (strlen($newKey) > 20) {
                        $newKey = substr($newKey, 0, 20);
                    }
                    $data[$newKey] = $data[$key];
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }

    /**
     * Returns information about the update wizard states
     */
    protected function getUpdateWizardStates()
    {
        if (!class_exists(\TYPO3\CMS\Install\Service\UpgradeWizardsService::class)) {
            return [];
        }
        $wizardRegistry = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'];
        $upgradeWizardsService = $this->coreApi->makeInstance(\TYPO3\CMS\Install\Service\UpgradeWizardsService::class);
        $upgradeWizardStates = [];
        foreach ($wizardRegistry as $identifier => $className) {
            if (empty($className) || !class_exists($className)) {
                continue;
            }
            if (class_exists(\TYPO3\CMS\Extbase\Object\ObjectManager::class)) {
                /* @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                $objectManager = $this->coreApi->makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
                $updateObject = $objectManager->get($className);
            } else {
                $updateObject = $this->coreApi->makeInstance($className);
            }
            /** @var UpgradeWizardInterface $updateObject */
            // Prevent exception for Update wizards, that use the deprecated \TYPO3\CMS\Install\Updates\AbstractUpdate
            if (interface_exists(TYPO3\CMS\Install\Updates\ChattyInterface::class)
                && is_a($updateObject, TYPO3\CMS\Install\Updates\ChattyInterface::class, true)) {
                $output = new \Symfony\Component\Console\Output\NullOutput();
                $updateObject->setOutput($output);
            }
            $shortIdentifier = $updateObject->getIdentifier();
            $upgradeWizardStates[$shortIdentifier] = [
                //'wizard' => $updateObject,
                'className' => $className,
                'title' => $updateObject->getTitle(),
                'explanation' => $updateObject->getDescription(),
                'confirmable' => false,
                'done' => false,
            ];
            if ($updateObject instanceof DatabaseRowsUpdateWizard) {
                $upgradeWizardStates = $this->extractRowUpdaters($updateObject, $upgradeWizardStates);
            }
            if ($updateObject instanceof ConfirmableInterface) {
                $upgradeWizardStates[$shortIdentifier]['confirmable'] = true;
            }
            try {
                $markedAsDone = $upgradeWizardsService->isWizardDone($shortIdentifier);
                if ($markedAsDone || !$updateObject->updateNecessary()) {
                    $upgradeWizardStates[$shortIdentifier]['done'] = true;
                }
            } catch (\RuntimeException $e) {
                $upgradeWizardStates[$shortIdentifier]['done'] = true;
                $upgradeWizardStates[$shortIdentifier]['_error'] = $e->getMessage();
            }
        }

        return $upgradeWizardStates;
    }

    private function extractRowUpdaters(DatabaseRowsUpdateWizard $rowsUpdateWizard, array $availableUpgradeWizards): array
    {
        $protectedProperty = 'rowUpdater';
        $availableRowUpdaters = \Closure::bind(function () use ($rowsUpdateWizard, $protectedProperty) {
            return $rowsUpdateWizard->$protectedProperty;
        }, null, $rowsUpdateWizard)();
        $upgradeWizardsService = $this->coreApi->makeInstance(\TYPO3\CMS\Install\Service\UpgradeWizardsService::class);
        foreach ($upgradeWizardsService->listOfRowUpdatersDone() as $rowUpdatersDone) {
            $availableUpgradeWizards[$rowUpdatersDone['class']] = [
                'className' => $rowUpdatersDone['class'],
                'title' => $rowUpdatersDone['title'],
                'explanation' => 'rowUpdater',
                'done' => true,
            ];
        }
        $notDoneRowUpdaters = array_diff($availableRowUpdaters, array_keys($availableUpgradeWizards));
        foreach ($notDoneRowUpdaters as $notDoneRowUpdater) {
            /** @var UpgradeWizardInterface $rowUpdater */
            $rowUpdater = $this->coreApi->makeInstance($notDoneRowUpdater);
            $availableUpgradeWizards[$notDoneRowUpdater] = [
                'className' => $notDoneRowUpdater,
                'title' => $rowUpdater->getTitle(),
                'explanation' => 'rowUpdater',
                'done' => false,
            ];
        }

        return $availableUpgradeWizards;
    }
}