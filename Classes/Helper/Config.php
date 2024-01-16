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
namespace BrainAppeal\T3monitor\Helper;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper for configuration
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Config implements SingletonInterface
{

    /**
     * The extension key
     *
     * @var string
     */
    public const EXT_KEY = 't3monitor';

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

    public function __construct()
    {
        $extConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::EXT_KEY);
        $this->setEncryptionKey($extConfig['encryption_key']);
        $excludeExtList = explode(',', $extConfig['exclude_local']);
        $this->setExcludeExtList($excludeExtList);
    }

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
     * @return int
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
}