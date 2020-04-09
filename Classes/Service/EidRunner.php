<?php
/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Brain Appeal GmbH (info@brain-appeal.com)
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
require_once $tmpExtPath . 'Classes/Service/Dispatcher.php';
require_once $tmpExtPath . 'Classes/Service/Compatibility.php';

$WDOG = Tx_T3monitor_Service_Compatibility::makeInstance('Tx_T3monitor_Service_Dispatcher');
$WDOG->run();