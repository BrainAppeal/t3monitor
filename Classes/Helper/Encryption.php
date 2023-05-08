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
/**
 * Helper for data encryption and decryption
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Helper
 */
class Encryption
{
    private $encryptionType = 'default';

    public function __construct()
    {
        if (function_exists('openssl_encrypt')) {
            $this->encryptionType = 'openssl';
        }
    }


    /**
     * Encrypt given string with given $key
     *
     * @param string $key The key used for encryption
     * @param string $string The key used for encryption
     * @return string The encrypted string
     * */
    public function encrypt($key, $string)
    {
        switch ($this->encryptionType) {
            case 'openssl':
                $encryptedStr = '01:' . $this->encryptOpenSsl($key, $string);
                break;
            default:
                $encryptedStr = $this->encryptDefault($key, $string);
                break;
        }
        return $encryptedStr;
    }

    /**
     * Decryption of string with given $key
     *
     * @param string $key The key used for decryption
     * @param string $encStr Encrypted string
     * @return string The decrypted string
     */
    public function decrypt($key, $encStr)
    {
        $encryptionType = $this->encryptionType;
        if (strpos($encStr, '01:') === 0) {
            $encStr = substr($encStr, 3);
            $encryptionType = 'openssl';
        }
        switch ($encryptionType) {
            case 'openssl':
                $decryptedStr = $this->decryptOpenSsl($key, $encStr);
                break;
            default:
                $decryptedStr = $this->decryptDefault($key, $encStr);
                break;
        }
        return $decryptedStr;
    }

    /**
     * OpenSSL encryption for given string with given $key
     *
     * @param string $key The key used for encryption
     * @param string $string The key used for encryption
     * @return string The encrypted string
     */
    private function encryptOpenSsl($key, $string)
    {
        $key = hash('sha256', substr($key, 0, 16));
        $iv = substr(hash('sha256', substr($key, 16)), 0, 16);
        $ciphertext = openssl_encrypt($string, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $output = base64_encode($ciphertext);
        return $output;
    }

    /**
     * OpenSSL decryption of given string with given $key
     *
     * @param string $key The key used for decryption
     * @param string $encStr Encrypted string
     * @return string The decrypted string
     */
    private function decryptOpenSsl($key, $encStr)
    {
        $key = hash('sha256', substr($key, 0, 16));
        $iv = substr(hash('sha256', substr($key, 16)), 0, 16);
        $output = openssl_decrypt(base64_decode($encStr), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $output;
    }

    /**
     * Fallback encryption for given string with given $key
     *
     * @param string $key The key used for encryption
     * @param string $string The key used for encryption
     * @return string The encrypted string
     */
    private function encryptDefault($key, $string)
    {
        $out = '';
        $cryptLen = strlen($key);
        for ($a = 0, $n = strlen($string); $a < $n; $a++) {
            $xorVal = ord($key[($a % $cryptLen)]);
            $out.= chr(ord($string[$a]) ^ $xorVal);
        }

        $str = base64_encode($out);
        $strHash = substr(md5($key . ':' . $str), 0, 10);
        return $strHash . ':' . $str;
    }

    /**
     * Fallback decryption of given string with given $key
     *
     * @param string $key The key used for decryption
     * @param string $encStr Encrypted string
     * @return string The decrypted string
     */
    private function decryptDefault($key, $encStr)
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
                    $xorVal = ord($key[($a % $cryptLen)]);
                    $out .= chr(ord($dcrStr[$a]) ^ $xorVal);
                }
                $dcrStr = $out;
            }
        }
        return $dcrStr;
    }
}