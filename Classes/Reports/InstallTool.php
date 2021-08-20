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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
class Tx_T3monitor_Reports_InstallTool extends Tx_T3monitor_Reports_Abstract
{
    /**
     * Returns information about the database tables
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
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
        $sqlReader = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\Schema\SqlReader::class);
        /** @var \TYPO3\CMS\Core\Database\Schema\SchemaMigrator $schemaMigrator */
        $schemaMigrator = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\Schema\SchemaMigrator::class);
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $schemaUpdates = [];
        try {
            $suggestions['add'] = $schemaMigrator->getUpdateSuggestions($sqlStatements);
            $suggestions['dropRename'] = $schemaMigrator->getUpdateSuggestions($sqlStatements, true);
            // Aggregate the per-connection statements into one flat array
            $schemaUpdates['add'] = [];

            foreach ($suggestions['add'] as $connectionName => $connectionUpdates) {
                foreach ($connectionUpdates as $groupName => $groupStatements) {
                    $schemaUpdates['add'][$groupName] = [];
                    foreach ($groupStatements as $statement) {
                        $schemaUpdates['add'][$groupName][] = $statement;
                    }
                }
            }

            $schemaUpdates['remove'] = [];
            foreach ($suggestions['dropRename'] as $connectionName => $connectionUpdates) {
                foreach ($connectionUpdates as $groupName => $groupStatements) {
                    $schemaUpdates['remove'][$groupName] = [];
                    foreach ($groupStatements as $statement) {
                        $schemaUpdates['remove'][$groupName][] = $statement;
                    }
                }
            }
        } catch (\Doctrine\DBAL\Schema\SchemaException $e) {
        } catch (\Doctrine\DBAL\DBALException $e) {
        } catch (\TYPO3\CMS\Core\Database\Schema\Exception\StatementException $e) {
        } catch (\TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException $e) {
        }

        return $schemaUpdates;
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
        $upgradeWizardsService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\UpgradeWizardsService::class);
        $upgradeWizardStates = [];
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        foreach ($wizardRegistry as $identifier => $className) {
            if (empty($className) || !class_exists($className)) {
                continue;
            }
            /** @var UpgradeWizardInterface $upgradeWizard */
            $updateObject = $objectManager->get($className);
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
            $markedAsDone = $upgradeWizardsService->isWizardDone($shortIdentifier);
            if ($markedAsDone || !$updateObject->updateNecessary()) {
                $upgradeWizardStates[$shortIdentifier]['done'] = true;
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
        $upgradeWizardsService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\UpgradeWizardsService::class);
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
            $rowUpdater = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($notDoneRowUpdater);
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