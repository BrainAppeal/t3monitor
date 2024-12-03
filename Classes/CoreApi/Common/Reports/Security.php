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

namespace BrainAppeal\T3monitor\CoreApi\Common\Reports;

use BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\InstallStatusReport;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\SecurityStatusReport;
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\Status;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Report class for security. Creates status reports similar to "reports" system extension
 *
 * @category TYPO3
 *
 * @see tx_reports_reports_Status
 */
class Security extends AbstractReport
{
    /**
     * Returns the system status reports
     *
     * @param \BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler
     */
    public function addReports(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler)
    {
        $reportsInfo = $this->getReportsFromExt();
        $this->addInstallDetailedChecks($reportsInfo);
        // If reports from parent class are empty, create reports manually
        if (empty($reportsInfo['typo3'])) {
            $reportsInfo['typo3'] = $this->getFallbackInstallReportsIfReportExtensionIsNotInstalled();
        }
        if (empty($reportsInfo['typo3']['Typo3Version'])) {
            $reportsInfo['typo3']['Typo3Version'] = [
                'value' => $this->coreApi->getTypo3Version(),
                'severity' => -2,
            ];
        }
        $additionalSecurityReports = $this->getSecurityReports();
        if (empty($reportsInfo['security'])) {
            $reportsInfo['security'] = $additionalSecurityReports;
        } else {
            $reportsInfo['security'] = array_merge($reportsInfo['security'], $additionalSecurityReports);
        }
        //Extend typo3 system reports with additional reports
        $this->addAdditionalReports($reportsInfo);
        $reportHandler->add('reports', $reportsInfo);
    }
    protected function addAdditionalReports(&$reportsInfo): void
    {
        // Find id of start page (root page of current site)
        $pageId = (int)$this->coreApi->getRootPageId();
        $reportsInfo['typo3']['StartPage'] = [
            'value' => (int)$this->coreApi->getRootPageId(),
            'severity' => empty($pageId) ? self::ERROR : self::OK,
        ];
        if (empty($reportsInfo['typo3']['Typo3Version'])) {
            $reportsInfo['typo3']['Typo3Version'] = [
                'value' => $this->coreApi->getTypo3Version(),
                'severity' => -2,
            ];
        }
    }

    protected function addInstallDetailedChecks(array &$reportsInfo): void
    {
        if (class_exists(\TYPO3\CMS\Install\SystemEnvironment\Check::class)) {
            $group = 'system';
            if (!isset($reportsInfo[$group])) {
                $reportsInfo[$group] = [];
            }
            try {
                /** @var \BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\Check $check */
                $check = GeneralUtility::makeInstance(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\Check::class);
                $statusList = $check->getStatusList();
            } catch (\Throwable $e) {
                $statusList = [
                    'checkException' => [
                        'value' => $e->getMessage(),
                        'severity' => 2,
                    ],
                ];
            }
            $reportsInfo[$group] = array_merge($reportsInfo[$group], $statusList);
            try {
                /** @var \BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\DatabaseCheck $databaseCheck */
                $databaseCheck = GeneralUtility::makeInstance(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\DatabaseCheck::class);
                $statusList = $databaseCheck->getStatusList();
            } catch (\Throwable $e) {
                $statusList = [
                    'databaseCheckException' => [
                        'value' => $e->getMessage(),
                        'severity' => 2,
                    ],
                ];
            }
            $reportsInfo[$group] = array_merge($reportsInfo[$group], $statusList);
            try {
                /** @var \BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\SetupCheck $databaseCheck */
                $setupCheck = GeneralUtility::makeInstance(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\SetupCheck::class);
                $statusList = $setupCheck->getStatusList();
            } catch (\Throwable $e) {
                $statusList = [
                    'setupCheckException' => [
                        'value' => $e->getMessage(),
                        'severity' => 2,
                    ],
                ];
            }
            $reportsInfo[$group] = array_merge($reportsInfo[$group], $statusList);
        }
    }

    /**
     * @return array
     */
    private function getFallbackInstallReportsIfReportExtensionIsNotInstalled(): array
    {
        $this->coreApi->getLanguageService();
        $statusProviderClasses = [
            InstallStatusReport::class,
            SecurityStatusReport::class,
        ];
        $reportsInfo = [];
        foreach ($statusProviderClasses as $statusProviderClass) {
            /** @var \BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\StatusProviderInterface $sObj */
            $statusProviderInstance = $this->coreApi->makeInstance($statusProviderClass);
            try {
                $statusObj = $statusProviderInstance->getStatus();
                foreach ($statusObj as $sKey => $sObj) {
                    /** @var Status $sObj */
                    $reportsInfo[$sKey] = [
                        'value' => $sObj->getValue(),
                        'severity' => $sObj->getSeverity(),
                    ];
                }

            } catch (\Throwable $e) {
                $message = 'Error in ' . __FILE__ . '::' . __LINE__ . ': '
                    . $e->getMessage() . ' [' . $e->getFile() . '::' . $e->getLine() . ']';
                if ($statusProviderInstance !== null) {
                    $message .= '[StatusProvider: ' . get_class($statusProviderInstance) . ']';
                }
                $reportsInfo['exceptions'][] = [
                    'value' => $message,
                    'severity' => 2,
                ];
                unset($e);
            }
        }
        return $reportsInfo;
    }

    /**
     * @see tx_reports_reports_status_SecurityStatus
     *
     * @return array
     */
    private function getSecurityReports(): array
    {
        $info = [];
        $info['adminUserAccount'] = $this->securityAdminAccount();

        $value = 'OK';
        $severity = self::OK;
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            $value = 'Insecure';
            $severity = self::ERROR;
        }
        $info['encryptionKeyEmpty'] = [
            'value' => $value,
            'severity' => $severity,
        ];

        $value = 'OK';
        $severity = self::OK;
        if (!defined('FILE_DENY_PATTERN_DEFAULT')) {
            // TYPO3 >= 11.5 => \TYPO3\CMS\Core\Resource\Security\FileNameValidator::DEFAULT_FILE_DENY_PATTERN
            $defaultFileDenyPattern = '\\.(php[3-8]?|phpsh|phtml|pht|phar|shtml|cgi)(\\..*)?$|\\.pl$|^\\.htaccess$';
        } else {
            $defaultFileDenyPattern = constant('FILE_DENY_PATTERN_DEFAULT');
        }
        if (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'])) {
            $fileDenyPattern = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'];
            $defaultParts = GeneralUtility::trimExplode('|', $defaultFileDenyPattern, true);
            $givenParts = GeneralUtility::trimExplode('|', $fileDenyPattern, true);
            $missingParts = array_diff($defaultParts, $givenParts);
            if (!empty($missingParts)) {
                $value = 'Insecure';
                $severity = self::ERROR;
            }
        } else {
            $fileDenyPattern = $defaultFileDenyPattern;
        }
        $info['fileDenyPattern'] = [
            'value' => $value,
            'severity' => $severity,
        ];
        $value = 'OK';
        $severity = self::OK;
        if ($fileDenyPattern !== $defaultFileDenyPattern
            && $this->verifyFilenameAgainstDenyPattern('.htaccess')) {
            $value = 'Insecure';
            $severity = self::ERROR;
        }
        $info['htaccessUpload'] = [
            'value' => $value,
            'severity' => $severity,
        ];
        $info['installToolEnabled'] = $this->securityInstallTool();
        $value = 'OK';
        $severity = self::OK;
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] === md5('joh316')) {
            $value = 'Insecure';
            $severity = self::ERROR;
        }
        $info['installToolPassword'] = [
            'value' => $value,
            'severity' => $severity,
        ];
        return $info;
    }

    private function verifyFilenameAgainstDenyPattern(string $filename): bool
    {
        $fileNameValidator = GeneralUtility::makeInstance(FileNameValidator::class);
        /** @var FileNameValidator $fileNameValidator */
        return $fileNameValidator->isValid($filename);
    }

    /**
     * Checks if a backend user "admin" exists with the password "password"
     *
     * @return array{value: string, severity: int} Check result
     */
    private function securityAdminAccount(): array
    {
        $severity = self::OK;
        $value = 'OK';
        $db = $this->coreApi->getDatabase();
        $where = 'username = ' . $db->fullQuoteStr('admin', 'be_users')
            . ' AND password = ' . $db->fullQuoteStr(md5('password'), 'be_users')
            . ' AND deleted = 0';
        $row = $db->fetchRow('uid, username, password', 'be_users', $where);
        if (!empty($row)) {
            $value = 'Insecure';
            $severity = self::ERROR;
        }
        return [
            'value' => $value,
            'severity' => $severity,
        ];
    }

    /**
     * Checks if the installation tool is enabled
     *
     * @return array{value: string, severity: int} Check result
     */
    private function securityInstallTool(): array
    {
        $value = 'Disabled';
        $severity = self::OK;
        $basePath = Environment::getPublicPath() . '/';
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
        return [
            'value' => $value,
            'severity' => $severity,
        ];
    }
    /**
     * Get status reports from system extension "reports" (Does not have to be installed)
     *
     * @return array Array with report infos; returns empty array if reports extension was not found
     */
    protected function getReportsFromExt(): array
    {
        $reportsInfo = [];
        // TYPO3 >= 10.4
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'])
            && interface_exists(\TYPO3\CMS\Reports\StatusProviderInterface::class)) {
            // Ensure that $GLOBALS['LANG'] is set
            $this->coreApi->getLanguageService();
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'] as $group => $statusProvidersList) {
                foreach ($statusProvidersList as $statusProvider) {
                    $statusProviderInstance = null;
                    try {
                        $statusProviderInstance = $this->coreApi->makeInstance($statusProvider);
                        if (is_a($statusProviderInstance, \TYPO3\CMS\Reports\StatusProviderInterface::class)) {
                            if (is_a($statusProviderInstance, \TYPO3\CMS\Reports\RequestAwareStatusProviderInterface::class)
                                && isset($GLOBALS['TYPO3_REQUEST'])) {
                                $statusList = $statusProviderInstance->getStatus($GLOBALS['TYPO3_REQUEST']);
                            } elseif (method_exists($statusProviderInstance, 'getDetailedStatus')) {
                                $statusList = $statusProviderInstance->getDetailedStatus();
                            } else {
                                $statusList = $statusProviderInstance->getStatus();
                            }
                            foreach ($statusList as $sKey => $sObj) {
                                /** @var \TYPO3\CMS\Reports\Status $sObj */
                                $reportsInfo[$group][$sKey] = [
                                    'value' => $sObj->getValue(),
                                    'severity' => Status::getSeverityAsInt($sObj->getSeverity()),
                                ];
                            }
                        }
                    } catch (\TYPO3\CMS\Core\Routing\RouteNotFoundException $e) {
                        // ignore route exceptions
                        unset($e);
                    } catch (\Throwable $e) {
                        $message = 'Error in ' . __FILE__ . '::' . __LINE__ . ': '
                            . $e->getMessage() . ' [' . $e->getFile() . '::' . $e->getLine() . ']';
                        if ($statusProviderInstance !== null) {
                            $message .= '[StatusProvider: ' . get_class($statusProviderInstance) . ']';
                        }
                        $reportsInfo['exceptions'][] = [
                            'value' => $message,
                            'severity' => 2,
                        ];
                        unset($e);
                    }
                }
            }
        }
        return $reportsInfo;
    }
}
