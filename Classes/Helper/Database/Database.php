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
 * Helper class for database access. Implements singleton pattern.
 * (t3lib_Singleton interface not used for compatibility with TYPO3 4.2)
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Tx_T3monitor_Helper_Database implements Tx_T3monitor_Helper_DatabaseInterface
{
    /**
     * Singleton instance
     *
     * @var Tx_T3monitor_Helper_Database
     */
    private static $_instance = null;
    /**
     * Connection to database?
     *
     * @var boolean
     */
    private $isConnected;

    /**
     * List of tables with table information
     *
     * @var array
     */
    private $tableInfo;
    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->init();
    }
    private function init()
    {
        $this->isConnected = true;
    }

    /**
     * (Static) function that returns the Singleton instance of this class.
     *
     * Usage: $db = Tx_T3monitor_Helper_Database::getInstance();
     *
     * @return Tx_T3monitor_Helper_Database The class instance (Singleton)
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new Tx_T3monitor_Helper_Database();
        }
        return self::$_instance;
    }
    /**
     * Returns whether database connection exists.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->isConnected;
    }

    /**
     * Find start page; If root page is shortcut, the tree is traversed
     * recursively until a standard content page is found.
     * If the page tree is not configured correctly, this function returns null.
     *
     * @return array|null
     */
    public function getStartPage()
    {
        $row = $this->findContentPageRow(0, 0);
        return $row;
    }
    /**
     * Find first content page row starting from root
     *
     * @param integer $pid Parent id
     * @param integer $uid Page id
     * @param integer $recCount Recursive call counter (MAX: 10)
     * @return array Page row array
     */
    private function findContentPageRow($pid, $uid, $recCount = 0)
    {
        //If shortcuts are not configured correctly, an infinite loop would be
        //possible (2 shortcuts referencing each other)
        //=> break after max. 10 recursive calls
        if($recCount > 10) return null;
        $select = 'uid, doktype, shortcut, shortcut_mode';
        $where = '';
        if($uid > 0){
            $where .= 'uid = '.$uid.' AND ';
        } else {
            $where .= 'pid = '.$pid.' AND ';
        }
        $where .= 'deleted = 0 AND hidden = 0 AND doktype < 254';
        $row = $this->fetchRow($select, 'pages', $where, 'sorting ASC');
        if(!empty($row)){
            //Shortcut
            if($row['doktype'] == 4) {
                $scPid = $row['uid'];
                $scUid = 0;
                //First subpage or random subpage of current page
                if($row['shortcut_mode'] == 0  && $row['shortcut'] > 0){
                    $scPid = 0;
                    $scUid = $row['shortcut'];
                }
                if($scPid == $pid && $scUid == $uid){
                    return null;
                }
                $row = $this->findContentPageRow($scPid, $scUid, $recCount+1);
            }
        }
        return $row;

    }

    /**
     * @return array
     */
    public function getTablesInfo()
    {
        $cp = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
        $defaultConnection = $cp->getConnectionByName(\TYPO3\CMS\Core\Database\ConnectionPool::DEFAULT_CONNECTION_NAME);

        $queryBuilder = $defaultConnection->createQueryBuilder();
        $queryBuilder->select('TABLE_NAME AS Table', 'TABLE_ROWS AS Rows', 'DATA_LENGTH AS Data_length')
            ->from('information_schema.TABLES');
        $tables = $queryBuilder->execute()->fetchAll();
        $correctedTables = [];
        foreach ($tables as $table) {
            $correctedTables[$table['Table']] = $table;
        }
        $this->tableInfo = $correctedTables;
        return $this->tableInfo;
    }

    /**
     * Load record from database
     *
     * @param string $select SELECT string
     * @param string $from FROM string
     * @param string $where WHERE string
     * @param string $orderBy Optional ORDER BY string
     *
     * @return array Table row array; false if empty
     */
    public function fetchRow($select, $from, $where, $orderBy = '')
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable($from);
        $queryBuilder->resetRestrictions();
        $select = explode(', ', $select);
        $statement = $queryBuilder
            ->select(...$select)
            ->from($from)
            ->where($where)
            ->execute();
        $result = $statement->fetch();
        return $result;
    }

    /**
     * Load record list from database
     *
     * @param string $select SELECT string
     * @param string $from FROM string
     * @param string $where WHERE string
     * @param string $orderBy ORDER BY string
     * @param string $limit Optional LIMIT value, if none, supply blank string.
     *
     * @return array Table rows or empty array
     */
    public function fetchList($select, $from, $where, $orderBy, $limit = '')
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable($from);
        $queryBuilder->resetRestrictions();
        $select = explode(', ', $select);
        $orderBy = explode(' ', $orderBy);
        $statement = $queryBuilder
            ->select(...$select)
            ->from($from)
            ->where($where)
            ->orderBy(...$orderBy);
        if ($limit !== '') {
            $statement->setMaxResults($limit);
        }
        $records = $statement->execute()->fetchAll();
        return $records;
    }

    /**
     * Escaping and quoting values for SQL statements.
     *
     * @param string $string
     * @param  string $table
     * @return string
     * @see \TYPO3\CMS\Core\Database\DatabaseConnection::fullQuoteStr
     */
    public function fullQuoteStr($string, $table)
    {
         return '\''.$string. '\'';
    }

    /**
     * Return the requested database variable
     * In this case only returns server version, because its the only thing needed
     *
     * @param string $variableName
     * @return string|null
     */
    public function getDatabaseVariable($variableName)
    {
        $cp = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
        $defaultConnection = $cp->getConnectionByName(\TYPO3\CMS\Core\Database\ConnectionPool::DEFAULT_CONNECTION_NAME);
        $result = $defaultConnection->getServerVersion();
        return $result;
    }

    /**
     * @internal
     */
    public function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB']; //NOT USED HERE
    }
}