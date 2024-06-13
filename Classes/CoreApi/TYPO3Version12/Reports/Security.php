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

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version12\Reports;

use BrainAppeal\T3monitor\Registry\StatusRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Report class for security. Creates status reports similar to "reports" system extension
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Security extends \BrainAppeal\T3monitor\CoreApi\Common\Reports\Security
{
    /**
     * Get status reports from system extension "reports" (Does not have to be installed)
     *
     * @return array Array with report infos; returns empty array if reports extension was not found
     */
    protected function getReportsFromExt(): array
    {
        $reportsInfo = [];
        if (!interface_exists(\TYPO3\CMS\Reports\StatusProviderInterface::class)) {
            return $reportsInfo;
        }
        $container = GeneralUtility::getContainer();
        if ($container->has(StatusRegistry::class) && interface_exists(\TYPO3\CMS\Reports\StatusProviderInterface::class)) {
            $this->coreApi->getLanguageService();
            /** @var StatusRegistry $statusRegistry */
            $statusRegistry = $container->get(StatusRegistry::class);
            $statusProvidersList = $statusRegistry->getProviders();
            foreach ($statusProvidersList as $statusProviderInstance) {
                try {
                    if (is_a($statusProviderInstance, \TYPO3\CMS\Reports\RequestAwareStatusProviderInterface::class)
                        && isset($GLOBALS['TYPO3_REQUEST'])) {
                        $statusList = $statusProviderInstance->getStatus($GLOBALS['TYPO3_REQUEST']);
                    } elseif (method_exists($statusProviderInstance, 'getDetailedStatus')) {
                        $statusList = $statusProviderInstance->getDetailedStatus();
                    } else {
                        $statusList = $statusProviderInstance->getStatus();
                    }
                    foreach ($statusList as $sKey => $sObj) {
                        /** @var \TYPO3\CMS\Reports\Status $sObj */
                        $reportsInfo[$statusProviderInstance->getLabel()][$sKey] = [
                            'value' => $sObj->getValue(),
                            'severity' => $sObj->getSeverity()->value,
                            'message' => $sObj->getMessage(),
                        ];
                    }
                } catch (\TYPO3\CMS\Core\Routing\RouteNotFoundException $e) {
                    // ignore route exceptions
                    unset($e);
                } catch (\Throwable $e) {
                    $message = 'Error in ' . __FILE__ . '::' . __LINE__ . ': '
                        . $e->getMessage() . ' [' . $e->getFile() . '::' . $e->getLine() . ']';
                    if ($statusProviderInstance !== null) {
                        $message .= '[StatusProvider: ' . get_class($statusProviderInstance) . ']';
                    }
                    $reportsInfo['exceptions'][] = [
                        'value' => $message,
                        'severity' => 2,
                    ];
                    unset($e);
                }
            }
        }
        return $reportsInfo;
    }
}
