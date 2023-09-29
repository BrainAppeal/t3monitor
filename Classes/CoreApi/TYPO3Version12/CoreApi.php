<?php

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version12;

use BrainAppeal\T3monitor\CoreApi\AbstractCoreApi;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
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

    protected function initTsfe(ServerRequestInterface $request): void
    {
        if (!isset($GLOBALS['BE_USER'])) {
            Bootstrap::initializeBackendUser(FrontendBackendUserAuthentication::class, $request);
            Bootstrap::initializeBackendAuthentication();
        }
        $site = $request->getAttribute('site', null);
        if (null === $site) {
            $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
            $site = reset($sites);
            if ($site instanceof Site) {
                $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('site', $site);
            } else {
                $site = new NullSite();
            }
        }
        $rootPageId = 1;
        if (!($site instanceof NullSite)) {
            /** @var LanguageServiceFactory $languageServiceFactory */
            $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
            $siteLanguage = $site->getDefaultLanguage();
            $GLOBALS['BE_USER']->user['lang'] = $siteLanguage->getTypo3Language();
            if (!isset($GLOBALS['LANG'])) {
                $GLOBALS['LANG'] = $languageServiceFactory->createFromSiteLanguage($siteLanguage);
            }
            $rootPageId = $site->getRootPageId();
        }
        /** @var Site $site */
        $pageArguments = $request->getAttribute('routing', null);
        if (null === $pageArguments) {
            $pageArguments = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Routing\PageArguments::class, $rootPageId, '0', []);
        }
        $nullFrontend = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\Frontend\NullFrontend::class, 'pages');
        $cacheManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        try {
            $cacheManager->registerCache($nullFrontend);
        } catch (\Exception $exception) {
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
    }
}
