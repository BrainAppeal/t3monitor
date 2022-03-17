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
 * Report class for security. Creates status reports similar to
 * "reports" system extension
 * Only works for TYPO3 >= 4.3
 * @see Tx_T3monitor_Reports_SecurityCompat for TYPO3 4.2 compatible output
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 *
 * @see tx_reports_reports_Status
 */
class Tx_T3monitor_Reports_Security extends Tx_T3monitor_Reports_Abstract
{
    /**
     * Class name prefixes for autloading
     *
     * @var array
     */
    private $loadPrefixes;

    /**
     * Returns the system status reports
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $reportsInfo = $this->getReportsFromExt();
        //Extend typo3 system reports with additional reports
        $this->addAdditonalReports($reportsInfo);
        $reportHandler->add('reports', $reportsInfo);
    }

    protected function addAdditonalReports(&$reportsInfo)
    {
        $reportsInfo['typo3']['StartPage'] = $this->getStartPageIdReport();
    }
    /**
     * Find id of start page (real id, no shortcuts)
     *
     * @return array Report data
     */
    private function getStartPageIdReport()
    {
        //id of start page; if null, rootline is not configured correctly
        $db = Tx_T3monitor_Helper_DatabaseFactory::getInstance();
        $startRow = $db->getStartPage();
        $pageId = 0;
        $severity = self::ERROR;
        if (!empty($startRow)) {
            $pageId = $startRow['uid'];
            $severity = self::OK;
        }
        $report = array(
            'value' => $pageId,
            'severity' => $severity,
        );
        return $report;
    }

    /**
     * Get status reports from system extension "reports"
     * (Does not have to be installed)
     * Requires TYPO3 version >= 4.3
     *
     * @return array|bool Array with report infos; returns false if reports Extension was not found
     */
    protected function getReportsFromExt()
    {
        $reportsInfo = array();
        // TYPO3 >= 10.4
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'])
            && interface_exists(\TYPO3\CMS\Reports\StatusProviderInterface::class)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'] as $group => $statusProvidersList) {
                foreach ($statusProvidersList as $statusProvider) {
                    $statusProviderInstance = Tx_T3monitor_Service_Compatibility::makeInstance($statusProvider);
                    if (is_a($statusProviderInstance, \TYPO3\CMS\Reports\StatusProviderInterface::class)) {
                        $statusObj = $statusProviderInstance->getStatus();
                        foreach ($statusObj as $sKey => $sObj) {
                            $reportsInfo[$group][$sKey] = array(
                                'value' => $sObj->getValue(),
                                'severity' => $sObj->getSeverity(),
                            );
                        }
                    }
                }
            }
        } else {
            $this->loadPrefixes = array('tx_reports_status');
            $autoloads = $this->initExtAutoloads();
            if(empty($autoloads)) {
                return false;
            }
            $statusProviders = $this->initExtStatusProviders($autoloads);
            foreach ($statusProviders as $group => $provider) {
                $reportsInfo[$group] = array();
                $statusObj = $provider->getStatus();
                foreach ($statusObj as $sKey => $sObj) {
                    $reportsInfo[$group][$sKey] = array(
                        'value' => $sObj->getValue(),
                        'severity' => $sObj->getSeverity(),
                    );
                }
            }
        }
        return $reportsInfo;
    }

    private function initExtStatusProviders($autoloads)
    {
        $statusProviders = array();
        foreach ($autoloads as $key => $loadClass) {
            $isValidKey = false;
            foreach ($this->loadPrefixes as $prefix) {
                $isValidKey = strpos($key, $prefix) !== false;
                if ($isValidKey)
                    break;
            }
            if ($isValidKey) {
                require_once $loadClass;
                $lKey = strtolower($key);
                if ((strpos($lKey, 'tx_reports_reports_status_') !== false
                    && $lKey != 'tx_reports_reports_status_status')
                    || $lKey == 'tx_install_report_installstatus') {
                    $statusProviderInstance = Tx_T3monitor_Service_Compatibility::makeInstance($key);
                    if ($statusProviderInstance instanceof tx_reports_StatusProvider) {
                        $group = strtolower(str_replace(
                            array('tx_reports_reports_status_', 'tx_install_report', 'status'), '', $key
                        ));
                        $statusProviders[$group] = $statusProviderInstance;
                    }
                }
            }
        }
        return $statusProviders;
    }

    /**
     *
     * @global string $TYPO3_LOADED_EXT Required to simulate that
     *  extension "reports" is installed
     */
    private function initExtAutoloads()
    {
        global $TYPO3_LOADED_EXT;
        $sysExts = array('reports', 'install',);
        $autoloads = array();
        //Prefixes of class keys that will be loaded
        foreach ($sysExts as $key) {
            $this->loadPrefixes[] = 'tx_' . $key . '_report';
            $sysExtPath = 'typo3/sysext/';
            $relPath = $sysExtPath . $key . '/';
            $basePath = Tx_T3monitor_Service_Compatibility::getPublicPath();
            $extPath = $basePath . $relPath;
            if (!file_exists($extPath)) {
                continue;
            }

            //reports extension is not set in $TYPO3_LOADED_EXT even when installed
            if (!isset($TYPO3_LOADED_EXT[$key])) {
                $TYPO3_LOADED_EXT[$key] = array(
                    'type' => 'S',
                    'siteRelPath' => $relPath,
                    'typo3RelPath' => 'sysext/' . $key . '/',
                    'ext_tables.php' => $extPath . 'ext_tables.php',
                    //'ext_typoscript_setup.txt' => $extPath.'ext_typoscript_setup.txt',
                );
            }
            $autoFile = $extPath . 'ext_autoload.php';
            if (file_exists($autoFile)) {
                $extAutoloads = require($extPath . 'ext_autoload.php');
                $autoloads = array_merge($autoloads, $extAutoloads);
            }
        }
        return $autoloads;
    }
}