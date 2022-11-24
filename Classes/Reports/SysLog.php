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
     * Returns information about the database tables
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $this->addSysLogReports($reportHandler);
        $this->addLogFileReports($reportHandler);
    }

    /**
     * Returns logging information from the log files
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    private function addLogFileReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        if (class_exists(\TYPO3\CMS\Core\Log\LogManager::class)
            && class_exists(\TYPO3\CMS\Core\Error\ErrorHandler::class)
            && class_exists(\TYPO3\CMS\Core\Log\Writer\FileWriter::class)) {
            $logManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class);
            $errorLogger = $logManager->getLogger(\TYPO3\CMS\Core\Error\ErrorHandler::class);
            $writers = $errorLogger->getWriters();
            $cWriter = $writers['critical'][0] ?? null;
            $info = [];
            if ($cWriter instanceof \TYPO3\CMS\Core\Log\Writer\FileWriter) {
                $logFile = $cWriter->getLogFile();
                $lines = $this->readLastLinesFromFile($logFile, 50);
                $info['log'] = $lines;
            }
            $varDirSizes = $this->getGroupedDirectorySize(\TYPO3\CMS\Core\Core\Environment::getVarPath());
            $info['dir_sizes'] = $varDirSizes;
            $reportHandler->add('var', $info);
        }
    }

    /**
     * Returns the directory sizes grouped by
     * @param string $basePath
     * @return array
     */
    function getGroupedDirectorySize(string $basePath): array
    {
        $bytesTotal = [];
        $path = realpath($basePath);
        if(!empty($path) && file_exists($path)){
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
                /** @var \SplFileInfo $object */
                $paths = explode(DIRECTORY_SEPARATOR, trim(str_replace($basePath, '', $object->getPath()), DIRECTORY_SEPARATOR));
                $bytesTotal[$paths[0]] += $object->getSize();

            }
        }
        return $bytesTotal;
    }

    /**
     * Reads the last X lines of the log file
     * 
     * @param string $absFilePath
     * @param int $lineCount
     * @return string[]|null
     */
    private function readLastLinesFromFile(string $absFilePath, int $lineCount): ?array
    {
        try {
            if (file_exists($absFilePath) && filesize($absFilePath) > 0) {
                $file = new SplFileObject($absFilePath, 'r');
                $file->seek(PHP_INT_MAX);
                $lastLine = $file->key();
                $lineOffset = max(0, $lastLine - $lineCount);
                if ($lineOffset > 0) {
                    $lines = new LimitIterator($file, $lineOffset, $lastLine); //n being non-zero positive integer
                    return array_filter(iterator_to_array($lines));
                }
            }
            return [];
        } catch (\Exception $e) {
            return [
                $e->getMessage() . ' ['.$e->getFile() . '::' . $e->getLine().']'
            ];
        }
    }

    /**
     * Returns logging information from the sys_log table
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    private function addSysLogReports(Tx_T3monitor_Reports_Reports $reportHandler): void
    {
        $info = array();
        $db = Tx_T3monitor_Helper_DatabaseFactory::getInstance();
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