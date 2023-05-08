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

/**
 * Report class for server.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Server extends AbstractReport
{
    /**
     * Create reports
     *
     * @param Reports $reportHandler
     */
    public function addReports(Reports $reportHandler)
    {
        $info = [];
        //System reports
        $system = [
            'OperatingSystem' => [
                'value' => php_uname(),
                'severity' => -2,
            ],
        ];

        $config = $this->getConfig();
        $isExtended = $config->getShowExtendedReports();
        if ($isExtended) {
            $system['ServerName'] = [
                'value' => $_SERVER['SERVER_NAME'],
                'severity' => -2,
            ];
            $system['ServerAddr'] = [
                'value' => $_SERVER['SERVER_ADDR'],
                'severity' => -2,
            ];
            $system['ServerApi'] = [
                'value' => php_sapi_name(),
                'severity' => -2,
            ];
        }

        $db = $this->coreApi->getDatabase();
        $currentMysqlVersion = $db->getDatabaseVariable('version');
        if (empty($currentMysqlVersion) && function_exists('mysql_get_client_info')){
            $currentMysqlVersion = mysql_get_client_info();
        }
        if (!empty($currentMysqlVersion)) {

            $system['MysqlVersion'] = [
                'value' => $currentMysqlVersion,
                'severity' => -2,
            ];
        }
        $info['system'] = $system;
        $configuration = [];
        $configuration['maxExecutionTime'] = [
            'value' => ini_get('max_execution_time'),
            'severity' => -2,
        ];
        $configuration['postMaxSize'] = [
            'value' => ini_get('post_max_size'),
            'severity' => -2,
        ];
        $configuration['maxInputVars'] = [
            'value' => ini_get('max_input_vars'),
            'severity' => -2,
        ];

        if ($isExtended) {
            $configuration['allowUrlFopen'] = [
                'value' => ini_get('allow_url_fopen'),
                'severity' => -2,
            ];
            $configuration['maxInputTime'] = [
                'value' => ini_get('max_input_time'),
                'severity' => -2,
            ];
            $configuration['displayErrors'] = [
                'value' => ini_get('display_errors'),
                'severity' => -2,
            ];
            $configuration['fileUploads'] = [
                'value' => ini_get('file_uploads'),
                'severity' => -2,
            ];
        }
        $info['configuration'] = $configuration;
        $reportHandler->add('reports', $info);
    }
}