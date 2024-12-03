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
use TYPO3\CMS\Core\Package\PackageManager;


/**
 * Report class for extensions
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Extension extends AbstractReport
{
    protected function getInstalledExtensions()
    {
        if (class_exists(\TYPO3\CMS\Extbase\Object\ObjectManager::class)) {
            /* @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = $this->coreApi->makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
            /* @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility */
            $listUtility = $objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\ListUtility::class);
            $extensions = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        } else {
            /* @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility */
            $listUtility = $this->coreApi->makeInstance(\TYPO3\CMS\Extensionmanager\Utility\ListUtility::class);
            $extensions = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        }
        return $extensions;
    }

    /**
     * Get reports for local extensions
     *
     * @param Reports $reportHandler
     * @throws \Throwable
     */
    public function addReports(Reports $reportHandler)
    {
        $loadedExtensions = [];
        $packageManager = $this->coreApi->makeInstance(PackageManager::class);
        foreach ($packageManager->getActivePackages() as $package) {
            /** @var \TYPO3\CMS\Core\Package\PackageInterface $package */
            $packageKey = $package->getPackageKey();
            // the full path to this package's main directory
            $packagePath = $package->getPackagePath();
            $isSystemExtension = strpos($packagePath, 'sysext' . DIRECTORY_SEPARATOR) !== false
                || strpos($packagePath, 'vendor/typo3' . DIRECTORY_SEPARATOR) !== false;
            if (!$isSystemExtension) {
                $require = $package->getValueFromComposerManifest('require');
                if ($require instanceof \stdClass) {
                    $require = json_decode(json_encode($require), true);
                }
                $loadedExtensions[$packageKey] = [
                    'key' => $packageKey,
                    'path' => $packagePath,
                    'composer' => [
                        'name' => (string) $package->getValueFromComposerManifest('name'),
                        'homepage' => (string) $package->getValueFromComposerManifest('homepage'),
                        'description' => (string) $package->getValueFromComposerManifest('description'),
                        'require' => $require,
                    ],
                ];
            }
        }
        $extensions = $this->getInstalledExtensions();
        $config = $this->getConfig();
        $excludeList = $config->getExcludeExtList();
        $noExcludes = empty($excludeList);

        $extOutput = array();
        foreach (array_keys($extensions) as $extKey) {
            // Only add info for local extension; skip all extensions in exclude list
            if (array_key_exists($extKey, $loadedExtensions)
                && ($noExcludes || !in_array($extKey, $excludeList, false))) {
                $extData = $loadedExtensions[$extKey];
                $absExtPath = rtrim($extData['path']) . DIRECTORY_SEPARATOR;
                $extInfo = $extensions[$extKey];
                $emConf = $extInfo;
                $extReport = [];
                $extReport['ext'] = $extKey;
                $extReport['title'] = $emConf['title'] ?? $extKey;
                $extReport['author'] = $emConf['author'] ?? '';
                $extReport['state'] = $emConf['state'] ?? 'stable';
                $extReport['description'] = $emConf['description'] ?? '';
                $extReport['version'] = $emConf['version'];
                $extReport['constraints'] = $emConf['constraints'] ?? [];
                $extReport['installedBy'] = $this->findUserWhoInstalledExtension($absExtPath);
                $extReport['composer'] = $extData['composer'] ?? [];
                $this->removeEmptyKeys($extReport['constraints']);
                $iconFile = self::getExtensionIcon($absExtPath, false);
                $extReport['icon_file'] = $iconFile;
                // set name of log file if it exists;
                // Required to create a link to the manual for custom extensions that are not in the TER
                $docFile = '';
                $docFilePaths = [
                    'Documentation/Readme.md',
                    'README.md',
                    'readme.md',
                    'doc/manual.sxw',
                    'Documentation/Index.rst',
                ];
                foreach ($docFilePaths as $filePath) {
                    if (file_exists($absExtPath . $filePath)) {
                        $docFile = $filePath;
                    }
                }
                $extReport['doc_file'] = $docFile;

                $extOutput[] = $extReport;
            }
        }
        $reportHandler->add('installed_extensions', $extOutput);
    }

    /**
     * Find extension icon
     *
     * @param string $extensionPath Path to extension directory.
     * @param bool $returnFullPath Return full path of file.
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon
     */
    private static function getExtensionIcon(string $extensionPath, bool $returnFullPath = false): string
    {
        $icon = '';
        // @deprecated In v13 remove the boolean array value and use the file location string as value again
        $locationsToCheckFor = [
            'Resources/Public/Icons/Extension.svg' => false,
            'Resources/Public/Icons/Extension.png' => false,
            'Resources/Public/Icons/Extension.gif' => false,
            'ext_icon.svg' => true,
            'ext_icon.png' => true,
            'ext_icon.gif' => true,
        ];
        foreach ($locationsToCheckFor as $fileLocation => $legacyLocation) {
            if (file_exists($extensionPath . $fileLocation)) {
                $icon = $fileLocation;
                break;
            }
        }
        return $returnFullPath ? $extensionPath . $icon : $icon;
    }

    /**
     * Find the user who most likely installed this extension. This cannot be
     * determined with absolute certainty, because no log entry is created for
     * this action. Instead, the function checks which users were logged in
     * at the time the extension was installed.
     *
     * @param string $absExtPath Absolute path to extension
     * @return string
     */
    private function findUserWhoInstalledExtension($absExtPath)
    {
        $userName = '';
        if (is_dir($absExtPath)) {
            $modTstamp = filemtime($absExtPath);
            $minLoginTstamp = $modTstamp - 86400;
            $select = 'userid, type, tstamp, action';
            $from = 'sys_log';
            $where = 'type = 255 AND tstamp > ' . $minLoginTstamp
                . ' AND tstamp < ' . $modTstamp;

            $db = $this->coreApi->getDatabase();
            $loginList = $db->fetchList($select, $from, $where, ['tstamp' => 'DESC']);
            krsort($loginList);
            $userList = [];
            foreach ($loginList as $row) {
                $userId = $row['userid'];
                $loggedIn = (int) $row['action'] === 1;
                $userList[$userId] = $loggedIn;
            }
            $beUsers = array();
            $userCount = count($userList);
            if ($userCount > 0) {
                $userIds = array_keys($userList);
                $select = 'uid, username, admin';
                $from = 'be_users';
                $where = 'uid IN ('.implode(', ', $userIds).') AND admin = 1';
                $beUsers = $db->fetchList($select, $from, $where, ['uid' => 'ASC']);
            }
            foreach ($beUsers as $userRow) {
                if (!empty($userName)) $userName .= ' OR ';
                $userName .= $userRow['username'];
            }
        }
        return $userName;
    }

    /**
     * Helper function to prevent errors in xml when configuration array
     * has empty values, e.g.
     * <pre>
     * ...
     *     'constraints' => array(
     *         'depends' => array(
     *              '' => '',//This will result in an xml error if not removed
     *         ),
     *         'conflicts' => array(
     *         ),
     *         'suggests' => array(
     *         ),
     *      ),
     * </pre>
     *
     * @param array|mixed $array
     */
    private function removeEmptyKeys(&$array): void
    {
        if (!empty($array) && is_array($array)) {
            foreach ($array as $key => &$value) {
                if ($key === '') {
                    unset($array[$key]);
                } elseif (is_array($value)) {
                    $this->removeEmptyKeys($value);
                }
            }
        }
    }
}
