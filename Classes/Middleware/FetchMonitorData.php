<?php

declare(strict_types=1);

namespace BrainAppeal\T3monitor\Middleware;

use BrainAppeal\T3monitor\Service\DataCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Process T3monitor data if set
 */
class FetchMonitorData implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        if (($queryParams !== [] && isset($queryParams['t3monitor']))
            || $request->getHeaderLine('X-T3monitor') === '1'
            || $request->getUri()->getPath() === '/t3monitor'
        ) {
            // Remove any output produced until now
            ob_clean();
            /** @var DataCollector $dataCollector */
            $dataCollector = GeneralUtility::makeInstance(DataCollector::class);
            return $dataCollector->processRequest($request);
        }
        return $handler->handle($request);
    }
}