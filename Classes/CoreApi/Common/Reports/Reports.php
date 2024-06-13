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
use BrainAppeal\T3monitor\CoreApi\Common\Reports\Fallback\Status;

/**
 * Timer for duration of function calls
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Reports
{
    /**
     * Contains timer infos for different keys
     *
     * @var array
     */
    private $data;
    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->data = [];
    }
    /**
     * Adds the given report data to the data
     *
     * @param string $key A unique identifier
     * @param array|mixed $value The report data
     */
    public function add(string $key, $value): void
    {
        $this->addRecursive($key, $value, $this->data);
    }

    /**
     * Recursive function to add key value pairs with multidimensional array
     *
     * @param string|int $key A unique identifier
     * @param array|string $value The report data
     * @param array|mixed $data Data array
     */
    private function addRecursive($key, $value, &$data): void
    {
        if (!isset($data[$key])) {
            if (is_array($value)) {
                $data[$key] = [];
                $sData =& $data[$key];
                foreach($value as $sKey => $sVal){
                    $this->addRecursive($sKey, $sVal, $sData);
                }
            } else if ($value instanceof \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity) {
                $data[$key] = Status::getSeverityAsInt($value);
            } else {
                $data[$key] = $value;
            }
        } else {
            $sData =& $data[$key];
            foreach($value as $sKey => $sVal){
                $this->addRecursive($sKey, $sVal, $sData);
            }
        }
    }
    /**
     * Returns all report information as an array
     *
     * @return array Reports data
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
