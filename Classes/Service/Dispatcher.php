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

if (class_exists('\TYPO3\CMS\Core\Utility\ExtensionManagementUtility')) {
    $tmpExtPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3monitor');
} else {
    $tmpExtPath = t3lib_extMgm::extPath('t3monitor');
}
require_once $tmpExtPath . 'Classes/Helper/Config.php';
require_once $tmpExtPath . 'Classes/Helper/Database/DatabaseInterface.php';
require_once $tmpExtPath . 'Classes/Helper/Database/Compatibility8Database.php';
if (PHP_VERSION_ID >= 50500) {
    require_once $tmpExtPath . 'Classes/Helper/Database/Database.php';
}
require_once $tmpExtPath . 'Classes/Helper/Database/DatabaseFactory.php';
require_once $tmpExtPath . 'Classes/Helper/Encryption.php';
require_once $tmpExtPath . 'Classes/Helper/Logger.php';
require_once $tmpExtPath . 'Classes/Helper/Timer.php';
require_once $tmpExtPath . 'Classes/Reports/Abstract.php';
require_once $tmpExtPath . 'Classes/Reports/Reports.php';
require_once $tmpExtPath . 'Classes/Reports/Database.php';
require_once $tmpExtPath . 'Classes/Reports/Disc.php';
require_once $tmpExtPath . 'Classes/Reports/Extension.php';
require_once $tmpExtPath . 'Classes/Reports/Internal.php';
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
class Tx_T3monitor_Service_Dispatcher
{

    /**
     * The extension key
     *
     * @var string
     */
    private $extKey = 't3monitor';
    /**
     * Configuration object
     *
     * @var Tx_T3monitor_Helper_Config
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
     * Main method to dump a file
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     * @throws \UnexpectedValueException
     */
    public function processRequest($request)
    {
        $data = $this->generateData();
        $xml = Tx_T3monitor_Service_Compatibility::getInstance()->array2xml($data, '', 0, 'xml');
        $crypt = new Tx_T3monitor_Helper_Encryption();
        $encKey = $this->config->getEncryptionKey(true);
        $encStr = $crypt->encrypt($encKey, $xml);
        /*header('Content-type: text/plain; charset=utf-8');
        print($encStr);
        exit();*/
        $response = new \TYPO3\CMS\Core\Http\Response();
        $response->getBody()->write($encStr);
        return $response;
    }

    /**
     * Initializes the class properties
     */
    private function init()
    {
        $comp = Tx_T3monitor_Service_Compatibility::getInstance();

        $comp->initTsfe();
        // Config
        $t3ver = Tx_T3monitor_Service_Compatibility::getTypo3Version(true);
        if ($t3ver >= 9000000) {
            $extConfig = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get($this->extKey);
        } else {
            $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
        }
        $this->config = new Tx_T3monitor_Helper_Config();
        $this->config->setEncryptionKey($extConfig['encryption_key']);
        $this->config->setLogfilePath('');
        $excludeExtList = explode(',', $extConfig['exclude_local']);
        $this->config->setExcludeExtList($excludeExtList);

        if (!defined('PATH_typo3') && !Tx_T3monitor_Service_Compatibility::isTypo3VersionGte10()) {
            define('PATH_typo3', PATH_site . TYPO3_mainDir);
        }
        if (!isset($GLOBALS['LANG']) || !is_object($GLOBALS['LANG'])) {
            $comp->initLang();
        }
    }

    /**
     * Runs the dispatcher and sends the encrypted report data
     */
    public function generateData()
    {
        $logFile = $this->config->getLogfilePath();
        $logger = new Tx_T3monitor_Helper_Logger($logFile);
        $activateLogging = $this->config->getActivateLogging();
        $logger->setEnabled($activateLogging);
        $params = $_GET;

        $secret = isset($params['secret']) ? $params['secret'] : '';
        $this->confirmKeysOrDie($secret, $logger);

        $db = Tx_T3monitor_Helper_DatabaseFactory::getInstance();
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
        $timer = new Tx_T3monitor_Helper_Timer();
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
            'internal' => 'Tx_T3monitor_Reports_Internal',
            'security' => 'Tx_T3monitor_Reports_SecurityCompat',
            'installed_extensions' => 'Tx_T3monitor_Reports_Extension',
            'database' => 'Tx_T3monitor_Reports_Database',
            'sys_log' => 'Tx_T3monitor_Reports_SysLog',
            'system' => 'Tx_T3monitor_Reports_Server',
            'disc' => 'Tx_T3monitor_Reports_Disc',
            'links' => 'Tx_T3monitor_Reports_Links',
            'applications' => 'Tx_T3monitor_Reports_Applications',
        );
        $enabledReports = array();
        if(isset($params['reports'])) {
            $enabledReports = explode(',', trim(strip_tags($params['reports'])));
        }
        $t3ver = Tx_T3monitor_Service_Compatibility::getTypo3Version(true);
        if ($t3ver >= 9000000 && in_array('install_tool', $enabledReports, false)) {
            $tmpExtPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3monitor');
            require_once $tmpExtPath . 'Classes/Reports/InstallTool.php';
            $reports['install_tool'] = 'Tx_T3monitor_Reports_InstallTool';
        }
        $reportHandler = new Tx_T3monitor_Reports_Reports();
        $exceptions = [];
        foreach($reports as $key => $className){
            if(in_array($key, $enabledReports, false)){
                $timer->start($key);
                $reportObj = Tx_T3monitor_Service_Compatibility::makeInstance($className);
                $reportObj->setConfig($this->config);
                try {
                    $reportObj->addReports($reportHandler);
                } catch (Exception $e) {
                    $exceptions[$e->getCode()] = array(
                        'value' => $e->getMessage(),
                        'severity' => 2,
                    );
                    unset($e);
                }
                $timer->stop($key);
            }
        }
        if (!empty($exceptions)) {
            $reportHandler->add('exceptions', $exceptions);
        }
        $siteName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $reportHandler->add('site_name', $siteName);

        $timer->stop('main');
        $reportHandler->add('timer', $timer->getSummary());
        return $reportHandler->toArray();
    }

    /**
     * Runs the dispatcher and sends the encrypted report data
     */
    public function run()
    {
        $this->sendOutputAsXmlData($this->generateData());
    }
    /**
     * Confirms that a valid secret and encryption key are configured and the
     * correct secret key is set in the request;
     * If not the dispatcher is stopped immediately and an error message is send
     *
     * @param string $key The required secret key
     * @param Tx_T3monitor_Helper_Logger $logger Logging instance
     */
    private function confirmKeysOrDie($key, $logger)
    {
        $isValid = true;
        $msg = '';
        $encryptionKey = $this->config->getEncryptionKey();
        if (strlen($encryptionKey) != 64) {
            $msg = 'ERROR: The encryption key is not configured or has the wrong format';
            $isValid = false;
        } elseif (empty($key)){
            $msg = 'ERROR: The secret key in the request is missing';
            $isValid = false;
        } elseif (strpos($encryptionKey, $key) !== 0){
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
        $xml = Tx_T3monitor_Service_Compatibility::getInstance()->array2xml($data, '', 0, 'xml');
        $crypt = new Tx_T3monitor_Helper_Encryption();
        $encKey = $this->config->getEncryptionKey(true);
        $encStr = $crypt->encrypt($encKey, $xml);
        header('Content-type: text/plain; charset=utf-8');
        print($encStr);
        exit();
    }
}
//if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3monitor/Classes/Service/Dispatcher.php']) {
//    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3monitor/Classes/Service/Dispatcher.php']);
//}