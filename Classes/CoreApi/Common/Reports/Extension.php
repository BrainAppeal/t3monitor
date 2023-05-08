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
use TYPO3\CMS\Core\Core\Environment;
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
    private function getInstalledExtensions()
    {
        $extensions = null;
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
     * Get reports for extensions that are installed in typo3conf/ext (local)
     *
     * @param \BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler
     * @throws Exception
     */
    public function addReports(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler)
    {
        global $TYPO3_LOADED_EXT;
        $loadedExtensions = [];
        $packageManager = $this->coreApi->makeInstance(PackageManager::class);
        foreach ($packageManager->getActivePackages() as $package) {
            $loadedExtensions[$package->getPackageKey()] = [
                'key' => $package->getPackageKey(),
                'path' => $package->getPackagePath(),
                'type' => strpos($package->getPackagePath(), 'sysext' . DIRECTORY_SEPARATOR) === false ? 'L' : 'S',
            ];
        }
        $extensions = $this->getInstalledExtensions();
        $config = $this->getConfig();
        $excludeList = $config->getExcludeExtList();
        $showModifiedFiles = $config->getShowModifiedFiles();
        $noExcludes = empty($excludeList);

        $extOutput = array();
        $basePath = Environment::getPublicPath() . '/';;
        $extPath = $basePath . 'typo3conf/ext/';
        // Generate output
        if (array_key_exists(0, $loadedExtensions)) {
            $loadedExtensionsWithKeys = [];
            foreach ($loadedExtensions as $loadedExtension) {
                $loadedExtensionsWithKeys[$loadedExtension['key']] = $loadedExtension;
            }
            $loadedExtensions = $loadedExtensionsWithKeys;
        }
        foreach (array_keys($extensions) as $extKey) {
            //Only add info for installed extension in typo3conf/ext (L=local)
            //Skip all extensions in exclude list
            if (array_key_exists($extKey, $loadedExtensions)
                && $loadedExtensions[$extKey]['type'] == 'L'
                && ($noExcludes || !in_array($extKey, $excludeList))) {

                $absExtPath = $extPath . $extKey . '/';
                $extInfo = $extensions[$extKey];
                $emConf = $extInfo;
                // TYPO3 < 6
                if (isset($emConf['EM_CONF'])) {
                    $emConf = $emConf['EM_CONF'];
                }
                $extReport = array();
                $extReport['ext'] = $extKey;
                $extReport['title'] = $emConf['title'];
                $extReport['author'] = $emConf['author'];
                $extReport['state'] = $emConf['state'];
                $extReport['description'] = $emConf['description'];
                $extReport['version'] = $emConf['version'];
                $extReport['constraints'] = $emConf['constraints'];
                $extReport['installedBy'] = $this->findUserWhoInstalledExtension($absExtPath);
                $this->removeEmptyKeys($extReport['constraints']);
                $iconFile = '';
                $staticIconRelPath = 'Resources/Public/Icons/Extension.svg';
                if (file_exists($absExtPath . $staticIconRelPath)) {
                    $iconFile = $staticIconRelPath;
                } elseif (isset($extInfo['ext_icon'])) {
                    $iconFile = $extInfo['ext_icon'];
                } elseif (!empty($extInfo['files'])) {
                    if (in_array('ext_icon.gif', $extInfo['files'])) {
                        $iconFile = 'ext_icon.gif';
                    } elseif (in_array('ext_icon.png', $extInfo['files'])) {
                        $iconFile = 'ext_icon.png';
                    }
                }
                $extReport['icon_file'] = $iconFile;
                if ($showModifiedFiles) {
                    $extReport['changed_files'] = $this->getExtModifiedFiles(
                        $extKey, $extInfo, $emConf
                    );
                }
                //set name of log file if it exists;
                //Required to create a link to the manual for custom extensions
                //that are not in the TER
                $docFile = '';
                if(file_exists($absExtPath . 'doc/manual.sxw')){
                    $docFile = 'doc/manual.sxw';
                }
                $extReport['doc_file'] = $docFile;

                $extOutput[] = $extReport;
            }
        }
        $reportHandler->add('installed_extensions', $extOutput);
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
            $orderBy = 'tstamp DESC';
            $where = 'type = 255 AND tstamp > ' . $minLoginTstamp
                . ' AND tstamp < ' . $modTstamp;

            $db = $this->coreApi->getDatabase();
            $loginList = $db->fetchList($select, $from, $where, $orderBy);
            krsort($loginList);
            $userList = array();
            foreach ($loginList as $row) {
                $userId = $row['userid'];
                $loggedIn = $row['action'] == 1;
                $userList[$userId] = $loggedIn;
            }
            $beUsers = array();
            $userCount = count($userList);
            if ($userCount > 0) {
                $userIds = array_keys($userList);
                $select = 'uid, username, admin';
                $from = 'be_users';
                $orderBy = 'uid ASC';
                $where = 'uid IN ('.implode(', ', $userIds).') AND admin = 1';
                $beUsers = $db->fetchList($select, $from, $where, $orderBy);
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
     * @param array $array
     */
    private function removeEmptyKeys(&$array){
        if (!empty($array) && is_array($array)) {
            foreach ($array as $key => &$value) {
                if (strlen($key) == 0) {
                    unset($array[$key]);
                } elseif (is_array($value)) {
                    $this->removeEmptyKeys($value);
                }
            }
        }
    }

    private function getExtModifiedFiles($extKey, $extInfo, $emConf)
    {
        $currentMd5Array = $this->serverExtensionMD5array($extKey, $extInfo);
        $affectedFiles = array();
        if (!empty($emConf['_md5_values_when_last_written'])
            && strcmp($emConf['_md5_values_when_last_written'], serialize($currentMd5Array))) {
            $lastWritten = unserialize($emConf['_md5_values_when_last_written']);
            $files = $this->findMD5ArrayDiff($currentMd5Array, $lastWritten);
            if (count($files)) {
                $affectedFiles = $files;
            }
        }
        return $affectedFiles;
    }

    /**
     * Creates a MD5-hash array over the current files in the extension
     *
     * @param	string  $extKey Extension key
     * @param	array   $conf   Extension information array
     * @return	array   MD5-keys
     */
    private function serverExtensionMD5array($extKey, $conf) {
        // TYPO3 < 6
        if ($this->emDetails !== null) {
            $md5Array = $this->emDetails->serverExtensionMD5array($extKey, $conf);
        // TYPO3 >= 6
        } else {
            if (class_exists(\TYPO3\CMS\Extbase\Object\ObjectManager::class)) {
                /* @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
                $objectManager = $this->coreApi->makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
                $fileUtility = $objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class);
            } else {
                $fileUtility = $this->coreApi->makeInstance(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class);
            }
            /* @var $fileUtility \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility */
            // Creates upload-array - including filelist.
            $excludePattern = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];

            if (method_exists($fileUtility, 'getExtensionDir')) {
                $extensionPath = $fileUtility->getExtensionDir($extKey);
            } else {
                $extensionPath = $fileUtility->getAbsoluteExtensionPath($extKey);
            }
            // Add trailing slash to the extension path, getAllFilesAndFoldersInPath explicitly requires that.
            $extensionPath = \TYPO3\CMS\Core\Utility\PathUtility::sanitizeTrailingSeparator($extensionPath);
            // Get all the files of the extension, but exclude the ones specified in the excludePattern
            $files = \TYPO3\CMS\Core\Utility\GeneralUtility::getAllFilesAndFoldersInPath(
                array(),	// No files pre-added
                $extensionPath,	// Start from here
                '',		// Do not filter files by extension
                false,		// Include subdirectories
                99,		// Recursion level
                $excludePattern	// Files and directories to exclude.
            );
            // Make paths relative to extension root directory.
            $relFiles = \TYPO3\CMS\Core\Utility\GeneralUtility::removePrefixPathFromList($files, $extensionPath);
            $md5Array = array();
            if (is_array($relFiles)) {
                // Traverse files.
                foreach ($relFiles as $relPath) {
                    if ($relPath != 'ext_emconf.php') {
                        $file = $extensionPath . $relPath;
                        $contentMd5 = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file);
                        $md5Array[$relPath] = substr(md5($contentMd5), 0, 4);
                    }
                }
            }
        }
        return $md5Array;
    }

    /**
     * Compares two arrays with MD5-hash values for analysis of which files has changed.
     *
     * @param	array   $current    Current values
     * @param	array   $past       Past values
     * @return	array   Affected files
     */
    private static function findMD5ArrayDiff($current, $past) {
        if (!is_array($current)) {
            $current = array();
        }
        if (!is_array($past)) {
            $past = array();
        }
        $filesInCommon = array_intersect($current, $past);
        $diff1 = array_keys(array_diff($past, $filesInCommon));
        $diff2 = array_keys(array_diff($current, $filesInCommon));
        $affectedFiles = array_unique(array_merge($diff1, $diff2));
        return $affectedFiles;
    }
}