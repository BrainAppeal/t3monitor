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

/**
 * Report class for disc.
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Reports
 */
class Disc extends AbstractReport
{
    /**
     * Create reports
     *
     * @param Reports $reportHandler
     */
    public function addReports(Reports $reportHandler)
    {
        $info = [];
        $basePath = Environment::getPublicPath() . '/';
        if (function_exists('disk_total_space') && function_exists('disk_free_space')) {
            $totalDiskSpace = disk_total_space($basePath);
            $freeDiskSpace = disk_free_space($basePath);
            $usedDiskSpace = $totalDiskSpace - $freeDiskSpace;
            $info['total_space'] = $totalDiskSpace;
            $info['free_space'] = $freeDiskSpace;
            $info['used_space'] = $usedDiskSpace;
        }
        $sizeInfo = $this->getDirSizeInfo($basePath);
        $dirSizes = $sizeInfo['subdirs'];
        $dirSizes['_root'] = $sizeInfo['root'];
        $dirSizes['_total'] = $sizeInfo['total'];
        $info['dir_sizes'] = $dirSizes;
        $reportHandler->add('disc', $info);
    }
    /**
     * Get size information for given directory
     *
     * @param string $dir
     * @return array
     */
    private function getDirSizeInfo(string $dir): array
    {
        $subDirList = [
            'subdirs' => [],
            'files' => [],
        ];
        $sumFileSize = 0;
        $sumTotal = 0;
        if (is_dir($dir) && $checkDir = opendir($dir)) {
            // add all files found to array
            while ($file = readdir($checkDir)) {
                $absPath = $dir . $file;
                if ($file !== '.' && $file !== '..'){
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
     * @return int Size of directory in bytes
     */
    private function dirSize(string $directory): int
    {
        $size = 0;
        if(!Environment::isWindows()){
            // Returns size in Kilobytes
            $result = explode("\t", exec("du --summarize ".$directory) , 2);
            if(count($result) > 1 && $result[1] === $directory){
                $size = $result[0];
            }
            // Kilobyte => Byte
            $size *= 1024;
        }
        if(empty($size)){
            //Returns size in Bytes
            $size = $this->dirSizeRecursive($directory);
        }
        return $size;
    }

    /**
     * Fallback function to calculate total size of dir
     *
     * @param string $directory Absolute path to directory
     * @return int Size of directory in bytes
     */
    private function dirSizeRecursive(string $directory): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            /* @var $file \SplFileInfo */
            $isReadable = true;
            try {
                $isReadable = !$file->isLink() && $file->isReadable();
            } catch (\Throwable $e) {
                unset($e);
            }
            if ($isReadable) {
                try {
                    $size += $file->getSize();
                } catch (\Throwable $e) {
                    unset($e);
                }
            }
        }
        return $size;
    }
}