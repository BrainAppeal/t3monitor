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

if (defined('PATH_t3lib') && file_exists(PATH_t3lib . 'class.t3lib_install.php')) {
    require_once(PATH_t3lib . 'class.t3lib_install.php');
}
/**
 * Report class for extensions
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_T3monitor_Reports_Extension extends Tx_T3monitor_Reports_Abstract
{

    /**
     * Extension list manager
     *
     * @var tx_em_Extensions_List|SC_mod_tools_em_index
     */
    private $emList;

    /**
     * Extension details manager
     *
     * @var tx_em_Extensions_Details|SC_mod_tools_em_index
     */
    private $emDetails;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initializes the class properties
     */
    private function init()
    {
        $comp = Tx_T3monitor_Service_Compatibility::getInstance();
        $t3ver = $comp->int_from_ver(TYPO3_version);
        if ($t3ver >= 6000000) {
            // Starting from TYPO3 6.1, the database will connect itself when
            // needed
            if ($t3ver < 6001000) {
                \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();
            }
        } elseif ($t3ver >= 4005000) {
            require_once(PATH_typo3 . '/sysext/em/classes/extensions/class.tx_em_extensions_list.php');
            require_once(PATH_typo3 . '/sysext/em/classes/extensions/class.tx_em_extensions_details.php');
            $this->emList = Tx_T3monitor_Service_Compatibility::makeInstance('tx_em_Extensions_List');
            $this->emDetails = Tx_T3monitor_Service_Compatibility::makeInstance('tx_em_Extensions_Details');
        } else {
            require_once(PATH_typo3 . '/mod/tools/em/class.em_index.php');
            $this->emList = Tx_T3monitor_Service_Compatibility::makeInstance('SC_mod_tools_em_index');

            //@see SC_mod_tools_em_index::init
            // GLOBAL Paths
            $this->emList->typePaths = Array(
                'S' => TYPO3_mainDir . 'sysext/',
                'G' => TYPO3_mainDir . 'ext/',
                'L' => 'typo3conf/ext/'
            );
            // GLOBAL BackPaths
            $this->emList->typeBackPaths = Array(
                'S' => '../../../',
                'G' => '../../../',
                'L' => '../../../../' . TYPO3_mainDir
            );
            // GLOBAL excludeForPackaging
            $this->emList->excludeForPackaging = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];
            $this->emDetails = $this->emList;
        }
    }
    private function getInstalledExtensions()
    {
        $extensions = null;
        if ($this->emList !== null) {
            $exts = $this->emList->getInstalledExtensions();
            if (!$exts || !$exts[0]){
                throw new Exception('ERROR: Extension list could not be loaded!');
            }
            $extensions = $exts[0];
        } else {
            /* @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            /* @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility */
            $listUtility = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
            $extensions = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        }
        return $extensions;
    }

    /**
     * Get reports for extensions that are installed in typo3conf/ext (local)
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     * @throws Exception
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        global $TYPO3_LOADED_EXT;
        $loadedExtensions = & $TYPO3_LOADED_EXT;
        $extensions = $this->getInstalledExtensions();
        $config = $this->getConfig();
        $excludeList = $config->getExcludeExtList();
        $showModifiedFiles = $config->getShowModifiedFiles();
        $noExcludes = empty($excludeList);

        $extOutput = array();
        $extPath = PATH_site . 'typo3conf/ext/';
        // Generate output
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
                if (isset($extInfo['ext_icon'])) {
                    $iconFile = $extInfo['ext_icon'];
                } elseif (in_array('ext_icon.gif', $extInfo['files'])) {
                    $iconFile = 'ext_icon.gif';
                } elseif (in_array('ext_icon.png', $extInfo['files'])) {
                    $iconFile = 'ext_icon.png';
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

            $db = Tx_T3monitor_Helper_Database::getInstance();
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
            // Creates upload-array - including filelist.
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
            );
            /* @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
            $objectName = 'TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility';
            /* @var $fileUtility \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility */
            $fileUtility = $objectManager->get($objectName);
            $excludePattern = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];

            $extensionPath = $fileUtility->getAbsoluteExtensionPath($extKey);
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