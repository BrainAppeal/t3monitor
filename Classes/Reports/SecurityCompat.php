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
require_once 'Security.php';
/**
 * Report class for security. Creates status reports similar to
 * "reports" system extension
 * Also works for TYPO3 4.2
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_T3monitor_Reports_SecurityCompat extends Tx_T3monitor_Reports_Security
{

    /**
     * Returns the system status reports
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $reportsInfo = $this->getReportsFromExt();
        if (empty($reportsInfo)) {
            $reportsInfo = array();
        }
        //If reports from parent class are empty, create reports manually
        if (empty($reportsInfo['typo3'])) {
            $reportsInfo['_install'] = $this->getInstallReports();
            $reportsInfo['typo3'] = array(
                'Typo3Version' => array(
                    'value' => Tx_T3monitor_Service_Compatibility::getTypo3Version(),
                    'severity' => -2,
                ),
            );
        }
        if (empty($reportsInfo['system'])) {
            $reportsInfo['system'] = $this->getSystemReports();
        }
        if (empty($reportsInfo['security'])) {
            $reportsInfo['security'] = $this->getSecurityReports();
        }
        if (empty($reportsInfo['configuration'])) {
            $reportsInfo['configuration'] = $this->getConfigurationReports();
        }
        //Extend typo3 system reports with additional reports
        $this->addAdditonalReports($reportsInfo);
        $reportHandler->add('reports', $reportsInfo);
    }

    /**
     * @see tx_install_report_InstallStatus
     *
     * @return array
     */
    private function getInstallReports()
    {
        $info = array();
        //@see tx_install_report_InstallStatus
        define('TYPO3_REQUIREMENTS_MINIMUM_PHP', '5.0.0');
        define('TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT', '8M');
        define('TYPO3_REQUIREMENTS_RECOMMENDED_PHP_MEMORY_LIMIT', '16M');
        $checkWritable = array(
            'typo3temp/' => 2,
            'typo3temp/pics/' => 2,
            'typo3temp/temp/' => 2,
            'typo3temp/llxml/' => 2,
            'typo3temp/cs/' => 2,
            'typo3temp/GB/' => 2,
            'typo3temp/locks/' => 2,
            'typo3conf/' => 2,
            'typo3conf/ext/' => 0,
            'typo3conf/l10n/' => 0,
            'uploads/' => 2,
            'fileadmin/' => -1,
        );
        $value = 'Writable';
        $severity = self::OK;
        $basePath = Tx_T3monitor_Service_Compatibility::getPublicPath();
        foreach ($checkWritable as $relPath => $requirementLevel) {
            $absPath = $basePath . $relPath;
            if (!@is_dir($absPath) || !is_writable($absPath)) {
                $severity = $requirementLevel;
                if ($severity == self::ERROR) {
                    $value = 'Directory not writable';
                    break;
                }
            }
        }
        $info['FileSystem'] = array(
            'value' => $value,
            'severity' => $severity,
        );
        $value = 'Update Complete';
        $severity = self::OK;
//        if (!Tx_T3monitor_Service_Compatibility::getInstance()->compat_version(TYPO3_branch)) {
//            $value = 'Update Incomplete';
//            $severity = self::WARNING;
//        }
        $info['RemainingUpdates'] = array(
            'value' => $value,
            'severity' => $severity,
        );
        return $info;
    }

    /**
     * @see tx_reports_reports_status_SystemStatus
     *
     * @return array
     */
    private function getSystemReports()
    {
        $info = array();
        $severity = self::OK;
        $value = phpversion();
        if (version_compare($value, TYPO3_REQUIREMENTS_MINIMUM_PHP) < 0) {
            $severity = self::ERROR;
        }
        $info['Php'] = array(
            'value' => $value,
            'severity' => $severity,
        );
        $severity = self::OK;
        $memoryLimit = ini_get('memory_limit');
        if (!empty($memoryLimit)) {
            $mlBytes = self::getBytesFromSizeMeasurement($memoryLimit);
            if ($mlBytes < self::getBytesFromSizeMeasurement(TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT)) {
                $severity = self::ERROR;
            } elseif ($mlBytes < self::getBytesFromSizeMeasurement(TYPO3_REQUIREMENTS_RECOMMENDED_PHP_MEMORY_LIMIT)) {
                $severity = self::WARNING;
            }
        }
        $info['PhpMemoryLimit'] = array(
            'value' => $memoryLimit,
            'severity' => $severity,
        );
        $severity = self::OK;
        $value = 'Disabled';
        $registerGlobals = trim(ini_get('register_globals'));
        // can't reliably check for 'on', therefore checking for the oposite 'off', '', or 0
        if (!empty($registerGlobals) && strtolower($registerGlobals) !== 'off') {
            $value = 'Enabled';
            $severity = self::ERROR;
        }
        $info['PhpRegisterGlobals'] = array(
            'value' => $value,
            'severity' => $severity,
        );
        $severity = self::OK;
        $info['Webserver'] = array(
            'value' => $_SERVER['SERVER_SOFTWARE'],
            'severity' => $severity,
        );
        return $info;
    }

    /**
     * Gets the bytes value from a measurement string like "100k".
     *
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement (not available in TYPO3 <= 4.2)
     *
     * @param	string		$measurement: The measurement (e.g. "100k")
     * @return	integer		The bytes value (e.g. 102400)
     */
    private static function getBytesFromSizeMeasurement($measurement)
    {
        $bytes = doubleval($measurement);
        if (stripos($measurement, 'G')) {
            $bytes *= 1024 * 1024 * 1024;
        } elseif (stripos($measurement, 'M')) {
            $bytes *= 1024 * 1024;
        } elseif (stripos($measurement, 'K')) {
            $bytes *= 1024;
        }
        return $bytes;
    }

    /**
     * @see tx_reports_reports_status_SecurityStatus
     *
     * @return array
     */
    private function getSecurityReports()
    {
        $info = array();
        $info['adminUserAccount'] = $this->securityAdminAccount();

        $value = 'OK';
        $severity = self::OK;
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            $value = 'Insecure';
            $severity = self::ERROR;
        }
        $info['encryptionKeyEmpty'] = array(
            'value' => $value,
            'severity' => $severity,
        );

        $value = 'OK';
        $severity = self::OK;
        if (!defined('FILE_DENY_PATTERN_DEFAULT')) {
            // TYPO3 >= 11.5 => \TYPO3\CMS\Core\Resource\Security\FileNameValidator::DEFAULT_FILE_DENY_PATTERN
            $defaultFileDenyPattern = '\\.(php[3-8]?|phpsh|phtml|pht|phar|shtml|cgi)(\\..*)?$|\\.pl$|^\\.htaccess$';
        } else {
            $defaultFileDenyPattern = constant('FILE_DENY_PATTERN_DEFAULT');
        }
        $compatibilityObject = Tx_T3monitor_Service_Compatibility::getInstance();
        if (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'])) {
            $fileDenyPattern = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'];
            $defaultParts = $compatibilityObject->trimExplode('|', $defaultFileDenyPattern, TRUE);
            $givenParts = $compatibilityObject->trimExplode('|', $fileDenyPattern, TRUE);
            $missingParts = array_diff($defaultParts, $givenParts);
            if (!empty($missingParts)) {
                $value = 'Insecure';
                $severity = self::ERROR;
            }
        } else {
            $fileDenyPattern = $defaultFileDenyPattern;
        }
        $info['fileDenyPattern'] = array(
            'value' => $value,
            'severity' => $severity,
        );
        $value = 'OK';
        $severity = self::OK;
        if ($fileDenyPattern !== $defaultFileDenyPattern
            && $compatibilityObject->verifyFilenameAgainstDenyPattern('.htaccess')) {
            $value = 'Insecure';
            $severity = self::ERROR;
        }
        $info['htaccessUpload'] = array(
            'value' => $value,
            'severity' => $severity,
        );
        $info['installToolEnabled'] = $this->securityInstallTool();
        $value = 'OK';
        $severity = self::OK;
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] == md5('joh316')) {
            $value = 'Insecure';
            $severity = self::ERROR;
        }
        $info['installToolPassword'] = array(
            'value' => $value,
            'severity' => $severity,
        );
        return $info;
    }
    /**
     * Checks if a backend user "admin" exists with the password "password"
     *
     * @return array Check result
     */
    private function securityAdminAccount()
    {
        $severity = self::OK;
        $value = 'OK';
        $db = Tx_T3monitor_Helper_DatabaseFactory::getInstance();
        $where = 'username = ' . $db->fullQuoteStr('admin', 'be_users')
            . ' AND password = ' . $db->fullQuoteStr(md5('password'), 'be_users')
            . ' AND deleted = 0';
        $row = $db->fetchRow('uid, username, password', 'be_users', $where);
        if (!empty($row)) {
            $value = 'Insecure';
            $severity = self::ERROR;
        }
        $checkResult = array(
            'value' => $value,
            'severity' => $severity,
        );
        return $checkResult;
    }
    /**
     * Checks if a the install tool is enabled
     *
     * @return array Check result
     */
    private function securityInstallTool()
    {
        $value = 'Disabled';
        $severity = self::OK;
        $basePath = Tx_T3monitor_Service_Compatibility::getPublicPath();
        $enableInstallToolFile = $basePath . 'typo3conf/ENABLE_INSTALL_TOOL';
        $enableInstallToolFileExists = is_file($enableInstallToolFile);
        if ($enableInstallToolFileExists) {
            if (trim(file_get_contents($enableInstallToolFile)) === 'KEEP_FILE') {
                $value = 'Enabled permanently';
                $severity = self::WARNING;
            } else {
                $enableInstallToolFileTtl = filemtime($enableInstallToolFile) + 3600 - time();
                if ($enableInstallToolFileTtl <= 0) {
                    unlink($enableInstallToolFile);
                } else {
                    $value = 'Enabled temporarily';
                    $severity = self::NOTICE;
                }
            }
        }
        $checkResult = array(
            'value' => $value,
            'severity' => $severity,
        );
        return $checkResult;
    }

    /**
     * @see tx_reports_reports_status_ConfigurationStatus
     *
     * @return array
     */
    private function getConfigurationReports()
    {
        $info = array();
        $value = 'OK';
        $severity = self::OK;
        /* $info['emptyReferenceIndex'] = array(
          'value' => $value,
          'severity' => $severity,
          );
          $value = 'OK';
          $severity = self::OK;
          $info['deprecationLog'] = array(
          'value' => $value,
          'severity' => $severity,
          ); */
        $value = 'Disabled';
        $severity = self::OK;
        $safeMode = strtolower(ini_get('safe_mode'));
        if ($safeMode === 'on' || $safeMode === 1) {
            $value = 'Enabled';
            $severity = self::WARNING;
        }
        $info['safeModeEnabled'] = array(
            'value' => $value,
            'severity' => $severity,
        );
        return $info;
    }
}