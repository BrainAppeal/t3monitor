<?php
/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Brain Appeal GmbH (info@brain-appeal.com)
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

/**
 * Report class for server.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_T3monitor_Reports_Internal extends Tx_T3monitor_Reports_Abstract
{
    /**
     * Create reports
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $comp = Tx_T3monitor_Service_Compatibility::getInstance();
        $t3ver = $comp->int_from_ver(TYPO3_version);
        if ($t3ver >= 6000000) {
            $internalReports = array();
            $status = array();
            $reportClasses = array();
            $checkPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('reports') . 'Classes/Report/Status/';
            $files = scandir($checkPath);
            foreach ($files as $file) {
                if (strpos($file, '.php') !== false) {
                    $fileName = str_replace( '.php', '', $file);
                    $key = trim(strtolower(str_replace('Status', '', $fileName)));
                    if (!empty($key)) {
                        $reportClasses[$key][] = 'TYPO3\\CMS\\Reports\\Report\\Status\\' . $fileName;
                    }
                }
            }
            $checkInstallPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Classes/Report/';
            $intallFiles = scandir($checkInstallPath);
            $mapKeys = array(
                'InstallStatusReport' => 'typo3',
                'SecurityStatusReport' => 'security',
                'EnvironmentStatusReport' => 'system',
            );
            foreach ($intallFiles as $file) {
                if (strpos($file, '.php') !== false) {
                    $fileName = str_replace( '.php', '', $file);
                    if (isset($mapKeys[$fileName])) {
                        $key = $mapKeys[$fileName];
                    } else {
                        $key = trim(strtolower(str_replace(array('Status', 'Report'), '', $fileName)));
                    }
                    $reportClasses[$key][] = 'TYPO3\\CMS\\Install\\Report\\' . $fileName;
                }
            }
            foreach ($reportClasses as $statusProviderId => $reportClassNames) {
                foreach ($reportClassNames as $reportClass) {
                    try {
                        $statusProviderInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($reportClass);
                        if (method_exists($statusProviderInstance, 'getStatus')) {
                            $statuses = $statusProviderInstance->getStatus();
                            if (empty($status[$statusProviderId])) {
                                $status[$statusProviderId] = $statuses;
                            } else {
                                $status[$statusProviderId] = array_merge($status[$statusProviderId], $statuses);
                            }
                        }
                    } catch (Exception $e) {
                        unset($e);
                    }
                }
            }
            foreach ($status as $group => $groupStatusList) {
                /** @var \TYPO3\CMS\Reports\Status $statusInstance */
                foreach ($groupStatusList as $statusName => $statusInstance) {
                    $internalReports[$group][$statusName] = array(
                        'value' => $statusInstance->getValue(),
                        'severity' => $statusInstance->getSeverity(),
                        'message' => $statusInstance->getMessage(),
                        'title' => $statusInstance->getTitle(),
                    );
                }
            }
            $reportHandler->add('reports', $internalReports);
        }
    }
}