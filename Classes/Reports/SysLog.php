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
 * Reports for sys log entries.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_T3monitor_Reports_SysLog extends Tx_T3monitor_Reports_Abstract
{
    /**
     * Returns informations about the database tables
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $info = array();
        $db = Tx_T3monitor_Helper_Database::getInstance();
        $config = $this->getConfig();
        $minTstamp = (int) $config->getMinTstamp();
        $limit = '';
        $tsCond = '';
        if($minTstamp > 0){
            $tsCond = ' AND tstamp > '.$minTstamp;
        }
        $select = 'tstamp, details, log_data';
        $from = 'sys_log';
        $orderBy = 'tstamp DESC';
        $limit = 30;

        //Load PHP errors
        $where = 'error = 1 AND type = 5'.$tsCond;
        $info['php_errors'] = $db->fetchList($select, $from, $where, $orderBy, $limit);

        //Successful backend logins
        $where = 'error = 0 AND type = 255'.$tsCond;
        $info['backend_logins'] = $db->fetchList($select, $from, $where, $orderBy, $limit);

        //Failed backend logins
        $where = 'error = 3'.$tsCond;
        $info['failed_backend_logins'] = $db->fetchList($select, $from, $where, $orderBy, $limit);

        $reportHandler->add('sys_log', $info);
    }
}