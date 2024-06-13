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

namespace BrainAppeal\T3monitor\Service;

use BrainAppeal\T3monitor\Helper\Config;
use BrainAppeal\T3monitor\Helper\Encryption;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main service class which creates and sends reports for this TYPO3 installation
 */
class DataResponseHandler
{
    public function createErrorResponse(\Throwable $e)
    {
        $message = $e->getMessage();
        return new HtmlResponse($message, 403, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Fetches the content and builds a content file out of it
     *
     * @param array $data the report data
     * @return ResponseInterface the modified response
     */
    public function createResponse(array $data): ResponseInterface
    {
        $config = GeneralUtility::makeInstance(Config::class);
        if ($encKey = $config->getEncryptionKey(true)) {
            $xml = GeneralUtility::array2xml($data, '', 0, 'xml');
            $crypt = new Encryption();
            $encStr = $crypt->encrypt($encKey, $xml);
        } else {
            $encStr = 'Incorrect configuration';
        }
        return new HtmlResponse($encStr, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
