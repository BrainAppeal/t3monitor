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
 * Helper for configuration
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Tx_T3monitor_Helper_Config
{
    /**
     * The secret encryption key; all send data are encrypted with this key.
     * This is used to prevent security issues if the secret key
     * which is send in the $_REQUEST from the Monitoring-Server is intercepted.
     * Without the encryption key, the returned data can not be read.
     *
     * @var string
     */
    private $encryptionKey = '';

    /**
     * Show changed files in extension
     *
     * @var boolean
     */
    private $showModifiedFiles;
    /**
     * Show additional information in reports. Useful for report data which
     * cost a lot of time to process
     *
     * @var boolean
     */
    private $showExtendedReports;
    /**
     * Minimum timestamp were changes are checked
     *
     * @var integer
     */
    private $minTstamp;

    /**
     * List of extensions to be excluded by this extension
     *
     * @var array
     */
    private $excludeExtList;

    /**
     * Enables or disables logging (Debugging only)
     *
     * @var boolean
     */
    private $activateLogging = false;
    /**
     * Absolute path to log file if logging is enabled
     *
     * @var string
     */
    private $logfilePath;

    /**
     * Returns the encryption key. If $forLocalEncryption flag is true, only the part of the key used for local
     * encryption of the data is used
     *
     * @param bool $forLocalEncryption
     * @return string
     */
    public function getEncryptionKey($forLocalEncryption = false)
    {
        return $forLocalEncryption ? substr($this->encryptionKey, 32) : $this->encryptionKey;
    }

    /**
     * Sets the encryption key
     *
     * @param string $encryptionKey
     */
    public function setEncryptionKey($encryptionKey)
    {
        $this->encryptionKey = (string) trim($encryptionKey);
    }

    /**
     *
     * @return boolean
     */
    public function getShowModifiedFiles()
    {
        return $this->showModifiedFiles;
    }

    /**
     *
     * @param boolean $showModifiedFiles
     */
    public function setShowModifiedFiles($showModifiedFiles)
    {
        $this->showModifiedFiles = (boolean) $showModifiedFiles;
    }

    /**
     *
     * @return boolean
     */
    public function getShowExtendedReports()
    {
        return $this->showExtendedReports;
    }

    /**
     *
     * @param boolean $showExtendedReports
     */
    public function setShowExtendedReports($showExtendedReports)
    {
        $this->showExtendedReports = (boolean) $showExtendedReports;
    }

    /**
     *
     * @return integer
     */
    public function getMinTstamp()
    {
        return $this->minTstamp;
    }

    public function setMinTstamp($minTstamp)
    {
        $this->minTstamp = (int) $minTstamp;
    }

    /**
     *
     * @return array
     */
    public function getExcludeExtList()
    {
        return $this->excludeExtList;
    }

    public function setExcludeExtList($excludeExtList)
    {
        $this->excludeExtList = (array) $excludeExtList;
    }

    /**
     *
     * @return boolean
     */
    public function getActivateLogging()
    {
        return $this->activateLogging;
    }

    /**
     *
     * @param boolean $activateLogging
     */
    public function setActivateLogging($activateLogging)
    {
        $this->activateLogging = $activateLogging;
    }

    /**
     *
     * @return string
     */
    public function getLogfilePath()
    {
        return $this->logfilePath;
    }

    /**
     *
     * @param string $logfilePath
     */
    public function setLogfilePath($logfilePath)
    {
        $this->logfilePath = $logfilePath;
    }
}