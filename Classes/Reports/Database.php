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
 * Reports for database.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_T3monitor_Reports_Database extends Tx_T3monitor_Reports_Abstract
{
    /**
     * Returns information about the database tables
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $dbInfo = array();
        $db = Tx_T3monitor_Helper_DatabaseFactory::getInstance();
        $tables = $db->getTablesInfo();
        $collations = array();
        foreach($tables as $table => $tInfo){
            $collation = strtolower($tInfo['collation']);
            $dbInfo[$table] = $tInfo;
            if (!array_key_exists('rows', $dbInfo[$table])) {
                $dbInfo[$table]['rows'] = 0;
            }
            if (!array_key_exists('data_length', $dbInfo[$table])) {
                $dbInfo[$table]['data_length'] = 0;
            }
            $collations[$collation] = 1;
        }
        $reportHandler->add('database', $dbInfo);
        $info = array();
        //Add report for used database collation
        $severity = self::INFO;
        $collations = array_keys($collations);
        if(count($collations) > 1){
            $severity = self::WARNING;
            $value = implode(', ', $collations);
        } else {
            $value = current($collations);
            //Show warning if utf-8 is not used
            if(strpos($value, 'utf8') === false){
                $severity = self::WARNING;
            }
        }

        //System reports
        $system = array(
            'DbCollation' => array(
                'value' => $value,
                'severity' => $severity,
            ),
        );
        $info['system'] = $system;
        $reportHandler->add('reports', $info);
    }
}