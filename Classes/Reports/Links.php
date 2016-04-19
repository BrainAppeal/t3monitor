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
 * Report class for links.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_T3monitor_Reports_Links extends Tx_T3monitor_Reports_Abstract
{
    /**
     * Create reports
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $info = array();
        $table = 'tx_linkvalidator_link';
        $db = Tx_T3monitor_Helper_Database::getInstance();
        $tables = $db->getTablesInfo();
        if (isset($tables[$table])) {
            $select = '*';
            $orderBy = 'uid ASC';
            $where = '';
            $info = $db->fetchList($select, $table, $where, $orderBy);
        }
        $reportHandler->add('linkvalidator', $info);
    }
}