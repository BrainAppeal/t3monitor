<?php

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version12;

use BrainAppeal\T3monitor\CoreApi\AbstractCoreApi;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Install\Report\InstallStatusReport;

/**
 * Compatibility layer for TYPO3 v12.4
 */
class CoreApi extends AbstractCoreApi
{

    /**
     * Creates an instance of a class taking into account the class-extensions
     *
     * @param string $className name of the class to instantiate, must not be empty
     * @param array<int, mixed> $constructorArguments Arguments for the constructor
     * @return object the created instance
     * @throws \InvalidArgumentException if class name is an empty string
     */
    public function makeInstance(string $className, ...$constructorArguments): object
    {
        if ($className === InstallStatusReport::class) {
            $upgradeWizardsService = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\UpgradeWizardsService::class);
            return GeneralUtility::makeInstance($className, $upgradeWizardsService);
        }
        return GeneralUtility::makeInstance($className, ...$constructorArguments);
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
        // 'frontend.typoscript' required for Extension report / QueryBuilder
        if (class_exists(FrontendTypoScript::class) && !$request->getAttribute('frontend.typoscript')) {
            /** @var FrontendTypoScript $frontendTypoScript */
            $frontendTypoScript = GeneralUtility::makeInstance(
                FrontendTypoScript::class,
                GeneralUtility::makeInstance(RootNode::class),
                []
            );
            $frontendTypoScript->setSetupArray([]);
            $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        }
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    public function getTsfe(): ?TypoScriptFrontendController
    {
        if (($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController) {
            return $GLOBALS['TSFE'];
        }
        try {
            /** @var ServerRequestInterface $request */
            $request = $GLOBALS['TYPO3_REQUEST'];
            if (!isset($GLOBALS['BE_USER'])) {
                Bootstrap::initializeBackendUser(FrontendBackendUserAuthentication::class, $request);
                Bootstrap::initializeBackendAuthentication();
            }
            $site = $this->getSite();
            if ($site instanceof SiteInterface) {
                $siteLanguage = $site->getDefaultLanguage();
                $GLOBALS['BE_USER']->user['lang'] = $siteLanguage->getTypo3Language();
            }
            // Ensure that $GLOBALS['LANG'] is set
            $this->getLanguageService();
            /** @var Site $site */
            $pageArguments = $request->getAttribute('routing', null);
            if (null === $pageArguments) {
                $rootPageId = $this->getRootPageId();
                $pageArguments = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Routing\PageArguments::class, $rootPageId, '0', []);
            }
            $nullFrontend = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\Frontend\NullFrontend::class, 'pages');
            $cacheManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
            try {
                $cacheManager->registerCache($nullFrontend);
            } catch (\Throwable $exception) {
                unset($exception);
            }
            // @see \TYPO3\CMS\Redirects\Service\RedirectService::bootFrontendController
            if (!isset($GLOBALS['TSFE']) || !$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
                $context = GeneralUtility::makeInstance(Context::class);
                $feUserAuth = GeneralUtility::makeInstance(FrontendUserAuthentication::class);

                $controller = GeneralUtility::makeInstance(
                    TypoScriptFrontendController::class,
                    $context,
                    $site,
                    $site->getDefaultLanguage(),
                    $pageArguments,
                    $feUserAuth
                );
                $originalRequest = $request;
                $controller->determineId($originalRequest);
                $controller->calculateLinkVars($originalRequest->getQueryParams());
                $GLOBALS['TSFE'] = $controller;
                $newRequest = $controller->getFromCache($originalRequest);
                $GLOBALS['TYPO3_REQUEST'] = $newRequest;
                $controller->releaseLocks();
                if (!$GLOBALS['TSFE']->sys_page instanceof PageRepository) {
                    $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
                }
            } elseif (!$request->getAttribute('frontend.typoscript')) {
                $originalRequest = $request;
                $newRequest = $GLOBALS['TSFE']->getFromCache($originalRequest);
                $GLOBALS['TYPO3_REQUEST'] = $newRequest;
            }
        } catch (\Throwable $e) {
            return null;
        }
        return $GLOBALS['TSFE'];
    }
}
