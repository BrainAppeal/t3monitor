<?php

declare(strict_types=1);

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version13;

use BrainAppeal\T3monitor\CoreApi\TYPO3Version12\CoreApi as CoreApiV12;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Compatibility layer for TYPO3 v13.4
 */
class CoreApi extends CoreApiV12
{
    protected $frontendTypoScriptFactory;
    protected $pageInformationFactory;

    public function setFrontendTypoScriptFactory(?FrontendTypoScriptFactory $frontendTypoScriptFactory = null)
    {
        $this->frontendTypoScriptFactory = $frontendTypoScriptFactory;
    }
    public function setPageInformationFactory(?\TYPO3\CMS\Frontend\Page\PageInformationFactory $pageInformationFactory = null)
    {
        $this->pageInformationFactory = $pageInformationFactory;
    }

    protected function initializeRequest(ServerRequestInterface $request): void
    {
        if (!isset($GLOBALS['TYPO3_REQUEST'])) {
            $GLOBALS['TYPO3_REQUEST'] = $request;
        }
        $this->getSite();

        if (!$request->getAttribute('normalizedParams')) {
            $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        }
        $request = $this->bootFrontendController($this->getSite(), $request->getQueryParams(), $request);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }
    protected function bootFrontendController(SiteInterface $site, array $queryParams, ServerRequestInterface $originalRequest): ServerRequestInterface
    {
        if ($originalRequest->getAttribute('frontend.typoscript')) {
            return $originalRequest;
        }
        if ($this->pageInformationFactory === null || $this->frontendTypoScriptFactory === null) {
            // 'frontend.typoscript' required for Extension report / QueryBuilder
            // Causes exception if not initialized: Setup array has not been initialized.
            // This happens in cached Frontend scope where full TypoScript is not needed by the system.
            // @see \TYPO3\CMS\Extensionmanager\Utility\ListUtility::getExtensionTerData
            if (class_exists(FrontendTypoScript::class)) {
                /** @var FrontendTypoScript $frontendTypoScript */
                $frontendTypoScript = GeneralUtility::makeInstance(
                    FrontendTypoScript::class,
                    GeneralUtility::makeInstance(RootNode::class),
                    [],
                    [],
                    []
                );
                $frontendTypoScript->setSetupArray([]);
                return $originalRequest->withAttribute('frontend.typoscript', $frontendTypoScript);
            }
            return $originalRequest;
        }
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('frontend.preview', new PreviewAspect());
        $cacheInstruction = $originalRequest->getAttribute('frontend.cache.instruction', new CacheInstruction());
        $originalRequest = $originalRequest->withAttribute('frontend.cache.instruction', $cacheInstruction);
        $queryParamsFromRequest = $originalRequest->getQueryParams();
        $mergedQueryParams = array_merge($queryParams, $queryParamsFromRequest);
        $originalRequest = $originalRequest->withQueryParams($mergedQueryParams);
        $pageArguments = new PageArguments($site->getRootPageId(), '0', []);
        $originalRequest = $originalRequest->withAttribute('routing', $pageArguments);
        $pageInformation = $this->pageInformationFactory->create($originalRequest);
        $originalRequest = $originalRequest->withAttribute('frontend.page.information', $pageInformation);
        $expressionMatcherVariables = $this->getExpressionMatcherVariables($site, $originalRequest);
        $this->initializePageRenderer($originalRequest, $expressionMatcherVariables);
        $frontendTypoScript = $this->frontendTypoScriptFactory->createSettingsAndSetupConditions(
            $site,
            $pageInformation->getSysTemplateRows(),
            // $originalRequest does not contain site ...
            $expressionMatcherVariables,
            null
        );
        // Note, that we need the full TypoScript setup array, which is required for links created by
        // DatabaseRecordLinkBuilder.
        $frontendTypoScript = $this->frontendTypoScriptFactory->createSetupConfigOrFullSetup(
            true,
            $frontendTypoScript,
            $site,
            $pageInformation->getSysTemplateRows(),
            $expressionMatcherVariables,
            '0',
            null,
            null
        );
        return $originalRequest->withAttribute('frontend.typoscript', $frontendTypoScript);
    }

    protected function initializePageRenderer(ServerRequestInterface $request, array &$expressionMatcherVariables): void
    {
        $controller = GeneralUtility::makeInstance(TypoScriptFrontendController::class);
        $controller->initializePageRenderer($request);
        $expressionMatcherVariables['tsfe'] = $controller;
    }

    protected function getExpressionMatcherVariables(SiteInterface $site, ServerRequestInterface $request): array
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $topDownRootLine = $pageInformation->getRootLine();
        $localRootline = $pageInformation->getLocalRootLine();
        ksort($topDownRootLine);
        return [
            'request' => $request,
            'pageId' => $pageInformation->getId(),
            'page' => $pageInformation->getPageRecord(),
            'fullRootLine' => $topDownRootLine,
            'localRootLine' => $localRootline,
            'site' => $site,
            'siteLanguage' => $request->getAttribute('language'),
        ];
    }
}
