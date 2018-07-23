<?php

interface Tx_T3monitor_Helper_DatabaseInterface
{
    public static function getInstance();

    public function isConnected();

    public function getStartPage();

    public function getTablesInfo();

    public function fetchRow($select, $from, $where, $orderBy = '');

    public function fetchList($select, $from, $where, $orderBy, $limit = '');

    public function fullQuoteStr($string, $table);

    public function getDatabaseVariable($variableName);

    public function getDatabaseConnection();
}