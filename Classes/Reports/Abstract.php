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
/**
 * Abstract report class
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 *
 * @see tx_reports_reports_Status
 */
abstract class Tx_MetT3monitor_Reports_Abstract
{
    //Constants copied from tx_reports_reports_Status
    const NOTICE = -2;
    const INFO = -1;
    const OK = 0;
    const WARNING = 1;
    const ERROR = 2;
    /**
     * Configuration object
     *
     * @var Tx_MetT3monitor_Helper_Config
     */
    private $config;

    /**
     * Asdsign configuration instance
     *
     * @param Tx_MetT3monitor_Helper_Config $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Returns configuration instance
     *
     * @return Tx_MetT3monitor_Helper_Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Adds the reports of this class to the report handler
     *
     * @param Tx_MetT3monitor_Reports_Reports $reportHandler
     */
    public abstract function addReports(Tx_MetT3monitor_Reports_Reports $reportHandler);
}
?>