<?php
/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 METEOS Deutschland (info@meteos.de)
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
 * Helper for data encryption and decryption
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Tx_MetT3monitor_Helper_Encryption
{

    /**
     * Encrypt given string with given $key
     *
     * @param string $key The key used for encryption
     * @param string $string The key used for encryption
     * @return string The encrypted string
     * */
    public function encrypt($key, $string)
    {
        $out = '';
        $cryptLen = strlen($key);
        for ($a = 0, $n = strlen($string); $a < $n; $a++) {
            $xorVal = ord($key{($a % $cryptLen)});
            $out.= chr(ord($string{$a}) ^ $xorVal);
        }

        $str = base64_encode($out);
        $strHash = substr(md5($key . ':' . $str), 0, 10);
        return $strHash . ':' . $str;
    }
    /**
     * Decrypt given string with given $key
     *
     * @param string $key The key used for decryption
     * @param string $encStr Encrypted string
     * @return string The decrypted string
     */
    public function decrypt($key, $encStr)
    {
        $dcrStr = '';
        $parts = explode(':', $encStr);
        $hash = $parts[0];
        $encData = isset($parts[1]) ? $parts[1] : '';

        $checkHash = substr(md5($key . ':' . $encData), 0, 10);
        if ($hash == $checkHash) {
            $dcrStr = base64_decode($encData);
            $strLen = strlen($dcrStr);
            $cryptLen = strlen($key);
            if ($cryptLen > 0) {
                $out = '';
                for ($a = 0; $a < $strLen; $a++) {
                    $xorVal = ord($key{($a % $cryptLen)});
                    $out .= chr(ord($dcrStr{$a}) ^ $xorVal);
                }
                $dcrStr = $out;
            }
        }
        return $dcrStr;
    }
}
?>