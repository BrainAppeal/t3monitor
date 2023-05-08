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
use BrainAppeal\T3monitor\CoreApi\CoreApiInterface;
use BrainAppeal\T3monitor\Helper\Config;

/**
 * Abstract report class
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 *
 * @see tx_reports_reports_Status
 */
abstract class AbstractReport
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
     * @var \BrainAppeal\T3monitor\Helper\Config
     */
    private $config;

    /**
     * Core API
     *
     * @var CoreApiInterface
     */
    protected $coreApi;

    public function __construct(CoreApiInterface $coreApi)
    {
        $this->coreApi = $coreApi;
        $this->config = $this->coreApi->makeInstance(Config::class);
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Adds the reports of this class to the report handler
     *
     * @param \BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler
     */
    public abstract function addReports(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler);
}