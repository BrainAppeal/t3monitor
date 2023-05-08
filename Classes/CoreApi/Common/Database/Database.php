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

namespace BrainAppeal\T3monitor\CoreApi\Common\Database;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Helper class for database access. Implements singleton pattern.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Database implements DatabaseInterface, SingletonInterface
{

    /**
     * List of tables with table information
     *
     * @var array
     */
    private $tableInfo;

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
     * @param int $pid Parent id
     * @param int $uid Page id
     * @param int $recCount Recursive call counter (MAX: 10)
     * @return array Page row array
     */
    private function findContentPageRow($pid, $uid, $recCount = 0)
    {
        //If shortcuts are not configured correctly, an infinite loop would be
        //possible (2 shortcuts referencing each other)
        //=> break after max. 10 recursive calls
        if($recCount > 10) {
            return null;
        }
        $select = 'uid, doktype, shortcut, shortcut_mode';
        $where = '';
        if($uid > 0){
            $where .= 'uid = '.$uid.' AND ';
        } else {
            $where .= 'pid = '.$pid.' AND ';
        }
        $where .= 'deleted = 0 AND hidden = 0 AND doktype < 254';
        $row = $this->fetchRow($select, 'pages', $where, 'sorting ASC');
        //Shortcut
        if(!empty($row) && (int) $row['doktype'] === 4) {
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
        return $row;

    }

    /**
     * @return array
     */
    public function getTablesInfo(): array
    {
        /** @var \TYPO3\CMS\Core\Database\ConnectionPool $cp */
        $cp = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
        $defaultConnection = $cp->getConnectionByName(\TYPO3\CMS\Core\Database\ConnectionPool::DEFAULT_CONNECTION_NAME);
        $database = $defaultConnection->getDatabase();
        $correctedTables = [];
        $hasCollationField = false;
        $queryBuilder = $defaultConnection->createQueryBuilder();
        try {
            $queryBuilder->select('*')
                ->from('information_schema.TABLES');
            $tables = $queryBuilder->execute()->fetchAll();
        } catch (\Exception $e) {
            $tables = [];
        }
        if (!empty($tables)) {
            $firstTable = current($tables);
            $hasCollationField = array_key_exists('TABLE_COLLATION', $firstTable);
            foreach ($tables as $table) {
                // TABLE_NAME AS Table', 'TABLE_ROWS AS Rows', 'DATA_LENGTH AS Data_length
                if ($table['TABLE_SCHEMA'] === $database && $table['TABLE_TYPE'] === 'BASE TABLE') {
                    $correctedTables[$table['TABLE_NAME']] = [
                        'name' => $table['TABLE_NAME'],
                        'rows' => (int) $table['TABLE_ROWS'],
                        'data_length' => $table['DATA_LENGTH'] ? $table['DATA_LENGTH'] : 0,
                        'collation' => $table['TABLE_COLLATION'] ? $table['TABLE_COLLATION'] : '',
                        'engine' => $table['ENGINE'] ? $table['ENGINE'] : '',
                    ];
                }
            }
        }
        if (!$hasCollationField && method_exists($defaultConnection, 'getSchemaManager')
            && null !== $schemaManager = $defaultConnection->getSchemaManager()) {
            $tableInfoList = $schemaManager->listTables();
            foreach ($tableInfoList as $tableInfo) {
                $name = $tableInfo->getName();
                $correctedTables[$name]['name'] = $name;
                $correctedTables[$name]['collation'] = $tableInfo->getOption('collation');
                $correctedTables[$name]['engine'] = $tableInfo->getOption('engine');
            }
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
        $statement = $queryBuilder;
//          ->select(...$select) // Cant use this because we need to ensure that the extension also works with < PHP5.6
        call_user_func_array(array($statement,'select'), $select);
        $queryResult = $statement->from($from)
            ->where($where)
            ->execute();
        $result = $queryResult->fetch();
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
        $statement = $queryBuilder;
//        ->select(...$select) // Cant use this because we need to ensure that the extension also works with < PHP5.6
        call_user_func_array(array($statement,'select'), $select);
        $statement->from($from)
            ->where($where);
//          ->orderBy(...$orderBy); // Cant use this because we need to ensure that the extension also works with < PHP5.6
        call_user_func_array(array($statement,'orderBy'), $orderBy);
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
}