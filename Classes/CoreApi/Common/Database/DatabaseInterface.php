<?php

namespace BrainAppeal\T3monitor\CoreApi\Common\Database;
interface DatabaseInterface
{

    public function getStartPage();

    public function getTablesInfo();

    public function fetchRow($select, $from, $where, $orderBy = '');

    public function fetchList($select, $from, $where, $orderBy, $limit = '');

    public function fullQuoteStr($string, $table);

    public function getDatabaseVariable($variableName);
}