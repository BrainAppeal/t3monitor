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

namespace BrainAppeal\T3monitor\CoreApi\Common\Reports;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Error\ErrorHandler;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

/**
 * Reports for sys log entries.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class SysLog extends AbstractReport
{
    /**
     * Returns information about the database tables
     *
     * @param \BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler
     */
    public function addReports(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler)
    {
        $this->addSysLogReports($reportHandler);
        $this->addLogFileReports($reportHandler);
    }

    /**
     * Returns logging information from the log files
     *
     * @param \BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler
     */
    private function addLogFileReports(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler)
    {
        /** @var LogManager $logManager */
        $logManager = $this->coreApi->makeInstance(LogManager::class);
        $errorLogger = $logManager->getLogger(ErrorHandler::class);
        $writers = $errorLogger->getWriters();
        $cWriter = $writers['critical'][0] ?? null;
        $info = [];
        if ($cWriter instanceof FileWriter) {
            $logFile = $cWriter->getLogFile();
            $lines = $this->readLastLinesFromFile($logFile, 50);
            $info['log'] = $lines;
        }
        $varPath = Environment::getVarPath();
        $varDirSizes = $this->getGroupedDirectorySize($varPath);
        $info['dir_sizes'] = $varDirSizes;
        $reportHandler->add('var', $info);
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
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $object){
                /** @var \SplFileInfo $object */
                $paths = explode(DIRECTORY_SEPARATOR, trim(str_replace($basePath, '', $object->getPath()), DIRECTORY_SEPARATOR));
                $pathKey = $paths[0];
                if (!isset($bytesTotal[$pathKey])) {
                    $bytesTotal[$pathKey] = 0;
                }
                $bytesTotal[$pathKey] += $object->getSize();

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
    private function readLastLinesFromFile(string $absFilePath, int $lineCount)
    {
        try {
            if (file_exists($absFilePath) && filesize($absFilePath) > 0) {
                $commandOutput = $this->tailCustom($absFilePath, $lineCount);
                if (!empty($commandOutput)) {
                    $lines = explode("\n", $commandOutput);
                    return array_filter($lines);
                }
            }
            return [];
        } catch (\Throwable $e) {
            return [
                $e->getMessage() . ' ['.$e->getFile() . '::' . $e->getLine().']'
            ];
        }
    }

    /**
    * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
    * @author Torleif Berger, Lorenzo Stanco
    * @link http://stackoverflow.com/a/15025877/995958
    * @license http://creativecommons.org/licenses/by/3.0/
    * @see https://gist.github.com/lorenzos/1711e81a9162320fde20
    */
    private function tailCustom($filepath, $lines = 1, $adaptive = true) {

        // Open file
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;

        // Sets buffer size, according to the number of lines to retrieve.
        // This gives a performance boost when reading a few lines from the file.
        if (!$adaptive) $buffer = 4096;
        else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

        // Jump to last character
        fseek($f, -1, SEEK_END);

        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) !== "\n") $lines -= 1;

        // Start reading
        $output = '';
        $chunk = '';

        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {

            // Figure out how far back we should jump
            $seek = min(ftell($f), $buffer);

            // Do the jump (backwards, relative to where we are)
            fseek($f, -$seek, SEEK_CUR);

            // Read a chunk and prepend it to our output
            $output = ($chunk = fread($f, $seek)) . $output;

            // Jump back to where we started reading
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

            // Decrease our line counter
            $lines -= substr_count($chunk, "\n");

        }

        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {

            // Find first newline and remove all text before that
            $output = substr($output, strpos($output, "\n") + 1);

        }

        // Close file and return
        fclose($f);
        return trim($output);

    }

    /**
     * Returns logging information from the sys_log table
     *
     * @param \BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler
     */
    private function addSysLogReports(\BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports $reportHandler)
    {
        $info = array();
        $db = $this->coreApi->getDatabase();
        $config = $this->getConfig();
        $minTstamp = (int) $config->getMinTstamp();
        $limit = '';
        $tsCond = '';
        if($minTstamp > 0){
            $tsCond = ' AND tstamp > '.$minTstamp;
        }
        $select = 'tstamp, details, log_data';
        $from = 'sys_log';
        $limit = 30;

        //Load PHP errors
        $where = 'error = 1 AND type = 5'.$tsCond;
        $info['php_errors'] = $db->fetchList($select, $from, $where, ['tstamp' => 'DESC'], $limit);

        //Successful backend logins
        $where = 'error = 0 AND type = 255'.$tsCond;
        $info['backend_logins'] = $db->fetchList($select, $from, $where, ['tstamp' => 'DESC'], $limit);

        //Failed backend logins
        $where = 'error = 3'.$tsCond;
        $info['failed_backend_logins'] = $db->fetchList($select, $from, $where, ['tstamp' => 'DESC'], $limit);

        $reportHandler->add('sys_log', $info);
    }
}