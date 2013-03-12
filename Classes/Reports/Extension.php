<?php
/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 METEOS Deutschland (info@meteos.de)
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

require_once(PATH_t3lib . 'class.t3lib_install.php');
/**
 * Report class for extensions
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_MetT3monitor_Reports_Extension extends Tx_MetT3monitor_Reports_Abstract
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
     * true if TYPO3 version is >= 4.5
     *
     * @var boolean
     */
    private $isVersion45;

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
        $this->isVersion45 = t3lib_div::int_from_ver(TYPO3_version) >= t3lib_div::int_from_ver('4.5.0');

        if ($this->isVersion45) {
            require_once(PATH_typo3 . '/sysext/em/classes/extensions/class.tx_em_extensions_list.php');
            require_once(PATH_typo3 . '/sysext/em/classes/extensions/class.tx_em_extensions_details.php');
            $this->emList = t3lib_div::makeInstance('tx_em_Extensions_List');
            $this->emDetails = t3lib_div::makeInstance('tx_em_Extensions_Details');
        } else {
            require_once(PATH_typo3 . '/mod/tools/em/class.em_index.php');
            $this->emList = t3lib_div::makeInstance('SC_mod_tools_em_index');

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

    /**
     * Get reports for extensions that are installed in typo3conf/ext (local)
     *
     * @param Tx_MetT3monitor_Reports_Reports $reportHandler
     * @throws Exception
     */
    public function addReports(Tx_MetT3monitor_Reports_Reports $reportHandler)
    {
        global $TYPO3_LOADED_EXT;
        $loadedExtensions = & $TYPO3_LOADED_EXT;

        $exts = $this->emList->getInstalledExtensions();
        if (!$exts || !$exts[0]){
            throw new Exception('ERROR: Extension list could not be loaded!');
        }
        $config = $this->getConfig();
        $excludeList = $config->getExcludeExtList();
        $showModifiedFiles = $config->getShowModifiedFiles();
        $noExcludes = empty($excludeList);

        // Generate output
        $extensions = $exts[0];
        $extOutput = array();
        $extPath = PATH_site . 'typo3conf/ext/';
        foreach (array_keys($extensions) as $extKey) {
            //Only add info for installed extension in typo3conf/ext (L=local)
            //Skip all extensions in exclude list
            if (array_key_exists($extKey, $loadedExtensions)
                && $loadedExtensions[$extKey]['type'] == 'L'
                && ($noExcludes || !in_array($extKey, $excludeList))) {

                $extInfo = $extensions[$extKey];
                $emConf = $extensions[$extKey]['EM_CONF'];
                $extReport = array();
                $extReport['ext'] = $extKey;
                $extReport['title'] = $emConf['title'];
                $extReport['author'] = $emConf['author'];
                $extReport['state'] = $emConf['state'];
                $extReport['description'] = $emConf['description'];
                $extReport['version'] = $emConf['version'];
                $extReport['constraints'] = $emConf['constraints'];
                $this->removeEmptyKeys($extReport['constraints']);
                $iconFile = '';
                if (in_array('ext_icon.gif', $extInfo['files'])) {
                    $iconFile = 'ext_icon.gif';
                }
                $extReport['icon_file'] = $iconFile;
                if ($showModifiedFiles) {
                    $extReport['changed_files'] = $this->getExtModifiedFiles($extKey, $extInfo, $emConf);
                }
                //set name of log file if it exists;
                //Required to create a link to the manual for custom extensions
                //that are not in the TER
                $docFile = '';
                if(file_exists($extPath.$extKey.'/doc/manual.sxw')){
                    $docFile = 'doc/manual.sxw';
                }
                $extReport['doc_file'] = $docFile;

                $extOutput[] = $extReport;
            }
        }
        $reportHandler->add('installed_extensions', $extOutput);
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
     * @param type $array
     */
    private function removeEmptyKeys(&$array){
        foreach ($array as $key => &$value) {
            if (strlen($key) == 0) {
                unset($array[$key]);
            } elseif (is_array($value)) {
                $this->removeEmptyKeys($value);
            }
        }
    }

    private function getExtModifiedFiles($extKey, $extInfo, $emConf)
    {
        $currentMd5Array = $this->emDetails->serverExtensionMD5array($extKey, $extInfo);
        $affectedFiles = array();
        if (strcmp($emConf['_md5_values_when_last_written'], serialize($currentMd5Array))) {

            $lastWritten = unserialize($emConf['_md5_values_when_last_written']);
            if ($this->isVersion45)
                $files = tx_em_Tools::findMD5ArrayDiff($currentMd5Array, $lastWritten);
            else
                $files = $this->emList->findMD5ArrayDiff($currentMd5Array, $lastWritten);

            if (count($files))
                $affectedFiles = $files;
        }
        return $affectedFiles;
    }
}