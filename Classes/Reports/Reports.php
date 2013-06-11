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
 * Timer for duration of function calls
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Tx_Brainmonitor_Reports_Reports
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
        $this->data = array();
    }
    /**
     * Adds the given report data to the data
     *
     * @return string $key A unique identifier
     * @return array $value The report data
     */
    public function add($key, $value)
    {
        $this->_add($key, $value, $this->data);
    }
    /**
     * Recursive function to add key value pairs with multidimensional array
     *
     * @param string $key A unique identifier
     * @param array|string $value The report data
     * @param array $data Data array
     */
    private function _add($key, $value, &$data)
    {
        if(!isset($data[$key]) || !is_array($value)){
            $data[$key] = $value;
        } else {
            $sData =& $data[$key];
            foreach($value as $sKey => $sVal){
                $this->_add($sKey, $sVal, $sData);
            }
        }
    }
    /**
     * Returns all report informations as an array
     *
     * @return array Reports data
     */
    public function toArray()
    {
        return $this->data;
    }
}