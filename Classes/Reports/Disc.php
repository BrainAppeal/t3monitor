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
 * Report class for disc.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Tx_T3monitor_Reports_Disc extends Tx_T3monitor_Reports_Abstract
{
    /**
     * Create reports
     *
     * @param Tx_T3monitor_Reports_Reports $reportHandler
     */
    public function addReports(Tx_T3monitor_Reports_Reports $reportHandler)
    {
        $info = array();
        $basePath = Tx_T3monitor_Service_Compatibility::getPublicPath();
        $totalDiskSpace = disk_total_space($basePath);
        $freeDiskSpace = disk_free_space($basePath);
        $usedDiskSpace = $totalDiskSpace - $freeDiskSpace;
        $info['total_space'] = $totalDiskSpace;
        $info['free_space'] = $freeDiskSpace;
        $info['used_space'] = $usedDiskSpace;
        $sizeInfo = $this->getDirSizeInfo($basePath);
        $dirSizes = $sizeInfo['subdirs'];
        $dirSizes['_root'] = $sizeInfo['root'];
        $dirSizes['_total'] = $sizeInfo['total'];
        $info['dir_sizes'] = $dirSizes;
        $reportHandler->add('disc', $info);
    }
    /**
     * Get size informations for given directory
     *
     * @param string $dir
     * @return array
     */
    private function getDirSizeInfo($dir)
    {
        $subDirList = array(
            'subdirs' => array(),
            'files' => array(),
        );
        $sumFileSize = 0;
        $sumTotal = 0;
        if (is_dir($dir) && $checkDir = opendir($dir)) {
            // add all files found to array
            while ($file = readdir($checkDir)) {
                $absPath = $dir . $file;
                if ($file != '.' && $file != '..'){
                    if (is_dir($absPath)){
                        $size = -1;
                        if(!is_link($absPath)){
                            $size = $this->dirSize($absPath . '/');
                            $sumTotal += $size;
                        }
                        $subDirList['subdirs'][$file] = $size;
                    } else {
                        $size = filesize($absPath);
                        $sumFileSize += $size;
                        $subDirList['files'][$file] = $size;
                    }
                }
            }
            closedir($checkDir);
        }
        $subDirList['root'] = $sumFileSize;
        $subDirList['total'] = $sumTotal;
        return $subDirList;
    }
    /**
     * Calculate the size of the given directory
     *
     * @param string $directory Absolute directory path
     * @return integer Size of directory in bytes
     */
    private function dirSize($directory)
    {
        $size = 0;
        if (class_exists('\\TYPO3\\CMS\\Core\\Core\\Environment')) {
            $osIsWindows = \TYPO3\CMS\Core\Core\Environment::isWindows();
        } else {
            $osIsWindows = TYPO3_OS != 'WIN';
        }
        if(!$osIsWindows){
            //Returns size in Kilobytes
            $result = explode("\t", exec("du --summarize ".$directory) , 2);
            if(count($result) > 1 && $result[1] == $directory){
                $size = $result[0];
            }
            //Kilobyte => Byte
            $size = $size * 1024;
        }
        if(empty($size)){
            //Returns size in Bytes
            $size = $this->_dirSize($directory);
        }
        return $size;
    }

    /**
     * Fallback function to calculate total size of dir
     *
     * @param string $directory Absolute path to directory
     * @return integer Size of directory in bytes
     */
    private function _dirSize($directory)
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            /* @var $file SplFileInfo */
            $isReadable = true;
            try {
                $isReadable = !$file->isLink() && $file->isReadable();
            } catch (Exception $e) {
                unset($e);
            }
            if ($isReadable) {
                try {
                    $size += $file->getSize();
                } catch (Exception $e) {
                    unset($e);
                }
            }
        }
        return $size;
    }
}