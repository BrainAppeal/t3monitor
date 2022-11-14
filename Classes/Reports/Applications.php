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
 * Report class for additional applications.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_T3monitor_Reports_Applications extends Tx_T3monitor_Reports_Abstract
{

    /**
     * Create reports
     *
     * @param Tx_T3monitor_Reports_Reports $dataHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $info = array();
        $cmsPublicPath = Tx_T3monitor_Service_Compatibility::getPublicPath();
        $checkApps = array(
            'piwik' => [
                'check_files' => [
                    $cmsPublicPath . 'piwik/core/Version.php',
                    dirname($cmsPublicPath) . '/piwik/core/Version.php',
                ],
                'pattern' => '/const VERSION\s*=\s*\'([^\']+)\';/',
            ],
            'matomo' => [
                'check_files' => [
                    $cmsPublicPath . 'matomo/core/Version.php',
                    dirname($cmsPublicPath) . '/matomo/core/Version.php',
                ],
                'pattern' => '/const VERSION\s*=\s*\'([^\']+)\';/',
            ],
        );
        foreach ($checkApps as $app => $appConfig) {
            $checkFiles = $appConfig['check_files'];
            foreach ($checkFiles as $vFile) {
                if (file_exists($vFile) && is_readable($vFile)) {
                    $fileContent = file_get_contents($vFile);
                    preg_match($appConfig['pattern'], $fileContent, $versionMatches);
                    if (!empty($versionMatches[1])) {
                        $info[ucfirst($app) . 'Version'] = array(
                            'value' => $versionMatches[1],
                            'severity' => -2,
                        );
                        break;
                    }
                }
            }
        }
        if (!empty($info)) {
            $reportsInfo = array();
            $reportsInfo['applications'] = $info;
            $reportHandler->add('reports', $reportsInfo);
        }
    }
}