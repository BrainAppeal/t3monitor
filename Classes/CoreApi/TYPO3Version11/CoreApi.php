<?php

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version11;

use BrainAppeal\T3monitor\CoreApi\AbstractCoreApi;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Compatibility layer for TYPO3 v11.5
 */
class CoreApi extends AbstractCoreApi
{

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
        $nullFrontend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\Frontend\NullFrontend::class, 'pages');
        $cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        try {
            $cacheManager->registerCache($nullFrontend);
        } catch (\Exception $exception) {
            unset($exception);
        }
        // @see \TYPO3\CMS\Redirects\Service\RedirectService::bootFrontendController
        if (!isset($GLOBALS['TSFE']) || !$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $context = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
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
            $controller->getFromCache($originalRequest);
            $GLOBALS['TYPO3_REQUEST'] = $originalRequest;
            $controller->releaseLocks();
            if (!$GLOBALS['TSFE']->sys_page instanceof PageRepository) {
                $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
            }
        }
    }
}
