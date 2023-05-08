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

namespace BrainAppeal\T3monitor\Helper;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Helper for logging
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Logger
{

    /**
     * Enable/disable logging
     *
     * @var boolean
     */
    private $enabled = false;

    /**
     *
     * @var string Absolute path to log file
     */
    private $absLogfile;

    /**
     * Cached log content
     *
     * @var string
     */
    private $logCache = '';

    /**
     * Number of log rows in log cache
     *
     * @var int
     */
    private $cachedLineCount = 0;

    /**
     * The constructor requires a log file name relative to the root path
     *
     * @param string $logFile
     */
    public function __construct($logFile)
    {
        $this->init($logFile);
    }

    /**
     * Initialize the logger instance; Disables logging if the given $logFile
     * is empty or is not writable
     *
     * @param string $logfile
     */
    private function init($logfile)
    {
        $this->enabled = false;
        if (!empty($logfile)) {
            $absPath = Environment::getPublicPath() . '/' . $logfile;
            $this->absLogfile = $absPath;
            $absDir = dirname($absPath);
            $this->enabled = is_dir($absDir) && is_writable($absDir);

            if ($this->enabled) {
                if (file_exists($this->absLogfile)) {
                    if ((filesize($this->absLogfile) > 1000000)) {
                        $fileEnd = strtolower(substr(strrchr($this->absLogfile, '.'), 0));
                        $newFileEnd = '_' . date('d-m-Y_H-i-s') . $fileEnd;
                        $oldLogName = str_replace($fileEnd, $newFileEnd, $this->absLogfile);
                        if (rename($this->absLogfile, $oldLogName)) {
                            @chmod($oldLogName, 0777);
                        }
                    }
                    $this->enabled = is_writable($this->absLogfile);
                }
            }
        }
    }

    /**
     * Enable or disable logging
     *
     * @param boolean $enabled true to enable, false to disable logging
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Log the given $content
     *
     * @param string|array $content
     */
    public function log($content)
    {
        if ($this->enabled && !empty($content)) {
            $txt = '';
            $txt .= date("d.m.Y H:i:s") . ' : ';
            if (is_array($content)) {
                $txt .= serialize($content);
            } else {
                $txt .= $content;
            }
            $txt .= "\n";
            $this->logCache .= $txt;
            ++$this->cachedLineCount;
            if ($this->cachedLineCount > 20) {
                $this->writeLogToFile();
            }
        }
    }

    /**
     * Write current log data to file
     *
     * @return boolean Returns true if log data were written to file or empty
     */
    private function writeLogToFile()
    {
        $isWritten = true;
        if ($this->enabled && !empty($this->logCache)) {
            $isWritten = file_put_contents($this->absLogfile, "\n" . $this->logCache, FILE_APPEND);
        }
        return $isWritten !== false;
    }

    /**
     * Write all cached log data to file when object is destroyed
     */
    public function __destruct()
    {
        $this->writeLogToFile();
    }
}