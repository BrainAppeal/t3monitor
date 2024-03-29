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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main service class which creates and sends reports for this TYPO3 installation
 *
 * @category TYPO3
 * @package T3Monitor
 * @subpackage Service
 */
class Dispatcher
{

    /**
     * Fetches the content and builds a content file out of it
     *
     * @param ServerRequestInterface $request the current request object
     * @return ResponseInterface the modified response
     * @throws \InvalidArgumentException
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        /** @var DataCollector $dataCollector */
        $dataCollector = GeneralUtility::makeInstance(DataCollector::class);
        return $dataCollector->processRequest($request);
    }
}