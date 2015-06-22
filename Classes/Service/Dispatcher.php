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

$tmpExtPath = t3lib_extMgm::extPath('brainmonitor');
require_once $tmpExtPath . 'Classes/Helper/Config.php';
require_once $tmpExtPath . 'Classes/Helper/Database.php';
require_once $tmpExtPath . 'Classes/Helper/Encryption.php';
require_once $tmpExtPath . 'Classes/Helper/Logger.php';
require_once $tmpExtPath . 'Classes/Helper/Timer.php';
require_once $tmpExtPath . 'Classes/Reports/Abstract.php';
require_once $tmpExtPath . 'Classes/Reports/Reports.php';
require_once $tmpExtPath . 'Classes/Reports/Database.php';
require_once $tmpExtPath . 'Classes/Reports/Disc.php';
require_once $tmpExtPath . 'Classes/Reports/Extension.php';
require_once $tmpExtPath . 'Classes/Reports/SecurityCompat.php';
require_once $tmpExtPath . 'Classes/Reports/Server.php';
require_once $tmpExtPath . 'Classes/Reports/SysLog.php';
require_once $tmpExtPath . 'Classes/Reports/Links.php';
require_once $tmpExtPath . 'Classes/Reports/Applications.php';
require_once $tmpExtPath . 'Classes/Service/Compatibility.php';
/**
 * Main service class which creates and sends reports for this TYPO3 installation
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Service
 */
class Tx_Brainmonitor_Service_Dispatcher
{

    /**
     * The extension key
     *
     * @var string
     */
    private $extKey = 'brainmonitor';
    /**
     * Configuration object
     *
     * @var Tx_Brainmonitor_Helper_Config
     */
    private $config;

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
        $comp = Tx_Brainmonitor_Service_Compatibility::getInstance();

        $comp->initTsfe();
        // Config
        $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);

        $this->config = new Tx_Brainmonitor_Helper_Config();
        $this->config->setSecretKey($extConfig['secret_key']);
        $this->config->setEncryptionkey($extConfig['encryptionkey']);
        $this->config->setLogfilePath('');
        $excludeExtList = explode(',', $extConfig['exclude_local']);
        $this->config->setExcludeExtList($excludeExtList);

        if (!defined(PATH_typo3)) {
            define('PATH_typo3', PATH_site . TYPO3_mainDir);
        }
        if (!is_object($GLOBALS['LANG'])) {
            $comp->initLang();
        }
    }
    /**
     * Runs the dispatcher and sends the encrypted report data
     */
    public function run()
    {
        $logFile = $this->config->getLogfilePath();
        $logger = new Tx_Brainmonitor_Helper_Logger($logFile);
        $activateLogging = $this->config->getActivateLogging();
        $logger->setEnabled($activateLogging);
        $params = $_GET;

        $secret = isset($params['secret']) ? $params['secret'] : '';
        $this->confirmKeysOrDie($secret, $logger);

        $db = Tx_Brainmonitor_Helper_Database::getInstance();
        if(!$db->isConnected()){
            die('ERROR: The current username, password or host was not '
                . 'accepted when the connection to the database was '
                . 'attempted to be established!');
        }

        $onlyCheckAccess = isset($params['only_check']) && $params['only_check'] == 1;
        if($onlyCheckAccess){
            die('OK');
        }

        // PARSE TIME BEGIN
        $timer = new Tx_Brainmonitor_Helper_Timer();
        $timer->start('main');
        // write Logfile
        $logger->log('TYPO3 Monitor called by IP: ' . $_SERVER['REMOTE_ADDR']);

        $showExtendedReports = isset($params['extended']) && $params['extended'] == 1;
        $showModifiedFiles = isset($params['changed_files']) && $params['changed_files'] == 1;
        //Timestamp of last check
        $lastCheck = isset($params['last_check']) ? (int) $params['last_check'] : 0;
        $this->config->setShowExtendedReports($showExtendedReports);
        $this->config->setShowModifiedFiles($showModifiedFiles);
        $this->config->setMinTstamp($lastCheck);

        $reports = array(
            'security' => 'Tx_Brainmonitor_Reports_SecurityCompat',
            'installed_extensions' => 'Tx_Brainmonitor_Reports_Extension',
            'database' => 'Tx_Brainmonitor_Reports_Database',
            'sys_log' => 'Tx_Brainmonitor_Reports_SysLog',
            'system' => 'Tx_Brainmonitor_Reports_Server',
            'disc' => 'Tx_Brainmonitor_Reports_Disc',
            'links' => 'Tx_Brainmonitor_Reports_Links',
            'applications' => 'Tx_Brainmonitor_Reports_Applications',
        );
        $enabledReports = array();
        if(isset($params['reports'])) {
            $enabledReports = explode(',', trim(strip_tags($params['reports'])));
        }
        $reportHandler = new Tx_Brainmonitor_Reports_Reports();
        foreach($reports as $key => $className){
            if(in_array($key, $enabledReports)){
                $timer->start($key);
                $reportObj = Tx_Brainmonitor_Service_Compatibility::makeInstance($className);
                $reportObj->setConfig($this->config);
                try {
                    $reportObj->addReports($reportHandler);
                } catch (Exception $e) {
                    die($e->getMessage());
                }
                $timer->stop($key);
            }
        }
        $siteName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $reportHandler->add('site_name', $siteName);

        $timer->stop('main');
        $reportHandler->add('timer', $timer->getSummary());
        $cmsData = $reportHandler->toArray();
        $this->sendOutputAsXmlData($cmsData);
    }
    /**
     * Confirms that a valid secret and encryption key are configured and the
     * correct secret key is set in the request;
     * If not the dispatcher is stopped immediately and an error message is send
     *
     * @param string $key The required secret key
     * @param Tx_Brainmonitor_Helper_Logger $logger Logging instance
     */
    private function confirmKeysOrDie($key, $logger)
    {
        $isValid = true;
        $msg = '';
        $secret = $this->config->getSecretKey();
        $encKey = $this->config->getEncryptionkey();
        if (trim($secret) == "") {
            $msg = 'ERROR: The secret key is not configured';
            $isValid = false;
        } elseif (trim($encKey) == "") {
            $msg = 'ERROR: The encryption key is not configured';
            $isValid = false;
        } elseif (empty($key)){
            $msg = 'ERROR: The secret key in the request is missing';
            $isValid = false;
        } elseif ($key != $secret){
            $msg = 'ERROR: The secret key in the request is wrong';
            $isValid = false;
        }
        if(!$isValid){
            $logger->log($msg);
            die($msg);
        }
    }
    /**
     * Echo $data array as an encrypted xml string.
     *
     * @param array $data The data array
     */
    private function sendOutputAsXmlData(array $data)
    {
        $xml = Tx_Brainmonitor_Service_Compatibility::getInstance()->array2xml($data, '', 0, 'xml');
        $crypt = new Tx_Brainmonitor_Helper_Encryption();
        $encKey = $this->config->getEncryptionkey();
        $encStr = $crypt->encrypt($encKey, $xml);
        header('Content-type: text/plain; charset=utf-8');
        print($encStr);
        exit();
    }
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/brainmonitor/Classes/Service/Dispatcher.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/brainmonitor/Classes/Service/Dispatcher.php']);
}
$WDOG = Tx_Brainmonitor_Service_Compatibility::makeInstance('Tx_Brainmonitor_Service_Dispatcher');
$WDOG->run();