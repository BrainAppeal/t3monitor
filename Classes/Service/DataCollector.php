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

use BrainAppeal\T3monitor\CoreApi\Common\Reports\Reports;
use BrainAppeal\T3monitor\CoreApi\CoreApiFactory;
use BrainAppeal\T3monitor\Exception\IncorrectConfigurationException;
use BrainAppeal\T3monitor\Helper\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main service class which creates and sends reports for this TYPO3 installation
 */
class DataCollector
{
    /**
     * @var ?LoggerInterface
     */
    private $logger;
    /**
     * Configuration object
     *
     * @var Config
     */
    private $config;

    /** @internal This connection can be only instantiated by its driver. */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->config = GeneralUtility::makeInstance(Config::class);
    }

    /**
     * Fetches the content and builds a content file out of it
     *
     * @param ServerRequestInterface $request the current request object
     * @return ResponseInterface the modified response
     * @throws \InvalidArgumentException
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        /** @var DataResponseHandler $responseHandler */
        $responseHandler = GeneralUtility::makeInstance(DataResponseHandler::class);
        try {
            $data = $this->collect($request);
        } catch (\Throwable $e) {
            return $responseHandler->createErrorResponse($e);
        }
        return $responseHandler->createResponse($data);
    }

    /**
     * Fetches the content and builds a content file out of it
     *
     * @param ServerRequestInterface $request the current request object
     * @return array the modified response
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws IncorrectConfigurationException
     */
    private function collect(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();
        $secret = $params['secret'] ?? '';
        $this->assertValidKeys($secret);

        $showExtendedReports = isset($params['extended']) && $params['extended'];
        // Timestamp of last check
        $lastCheck = isset($params['last_check']) ? (int) $params['last_check'] : 0;
        $this->config->setShowExtendedReports($showExtendedReports);
        $this->config->setMinTstamp($lastCheck);

        $this->initialize($request);
        return $this->generateData($params);
    }

    /**
     * Initializes the class properties
     * @param ServerRequestInterface $request
     */
    private function initialize(ServerRequestInterface $request): void
    {
        $compatFactory = GeneralUtility::makeInstance(CoreApiFactory::class);
        $compatInstance = $compatFactory->getCoreApi();
        $compatInstance->initialize($request);
    }

    /**
     * Runs the dispatcher and sends the encrypted report data
     */
    private function generateData(array $params)
    {
        $onlyCheckAccess = isset($params['only_check']) && $params['only_check'];
        if($onlyCheckAccess){
            die('OK');
        }

        // PARSE TIME BEGIN
        $timer = new \BrainAppeal\T3monitor\Helper\Timer();
        $timer->start('main');
        // write Logfile
        if (null !== $this->logger) {
            $this->logger->info(sprintf('TYPO3 Monitor called by IP: %s', $_SERVER['REMOTE_ADDR']));
        }

        $coreApiFactory = GeneralUtility::makeInstance(CoreApiFactory::class);
        $coreApi = $coreApiFactory->getCoreApi();
        $reportInstances = $coreApi->getReportInstances($params);
        $reportHandler = new Reports();
        $exceptions = [];
        foreach($reportInstances as $key => $reportObj){
            $timer->start($key);
            try {
                $reportObj->addReports($reportHandler);
            } catch (\Throwable $e) {
                $exceptions[$e->getCode()] = [
                    'value' => 'Exception in report ' . get_class($reportObj) . ': ' . $e->getMessage(),
                    'severity' => 2,
                ];
                unset($e);
            }
            $timer->stop($key);
        }
        if (!empty($exceptions)) {
            $reportHandler->add('exceptions', $exceptions);
        }
        $siteName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $reportHandler->add('site_name', $siteName);

        $timer->stop('main');
        $reportHandler->add('timer', $timer->getSummary());
        return $reportHandler->toArray();
    }

    /**
     * Confirms that a valid secret and encryption key are configured and the
     * correct secret key is set in the request;
     * If not the dispatcher is stopped immediately and an error message is send
     *
     * @param string $key The required secret key
     * @throws IncorrectConfigurationException
     */
    private function assertValidKeys(string $key): void
    {
        $isValid = true;
        $msg = '';
        $encryptionKey = $this->config->getEncryptionKey();
        if (strlen($encryptionKey) !== 64) {
            $msg = 'ERROR: The encryption key is not configured or has the wrong format';
            $isValid = false;
        } elseif (empty($key)){
            $msg = 'ERROR: The secret key in the request is missing';
            $isValid = false;
        } elseif (strpos($encryptionKey, $key) !== 0){
            $msg = 'ERROR: The secret key in the request is wrong';
            $isValid = false;
        }
        if (!$isValid){
            if (null !== $this->logger) {
                $this->logger->error($msg);
            }
            throw new IncorrectConfigurationException($msg);
        }
    }
}