<?php

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version11;

use BrainAppeal\T3monitor\CoreApi\AbstractCoreApi;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Compatibility layer for TYPO3 v11.5
 */
class CoreApi extends AbstractCoreApi
{
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
            $nullFrontend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\Frontend\NullFrontend::class, 'pages');
            $cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
            try {
                $cacheManager->registerCache($nullFrontend);
            } catch (\Throwable $exception) {
                unset($exception);
            }// @see \TYPO3\CMS\Redirects\Service\RedirectService::bootFrontendController
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
        } catch (\Throwable $e) {
            return null;
        }
        return $GLOBALS['TSFE'];
    }
}
