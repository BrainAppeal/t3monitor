<?php

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version10;

use BrainAppeal\T3monitor\CoreApi\AbstractCoreApi;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Compatibility layer for TYPO3 v10.4
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
            $rootPageId = $this->getRootPageId();
            $site = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Site\Entity\Site::class, $rootPageId, 1, []);
            $siteLanguage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Site\Entity\SiteLanguage::class,
                0,
                'en-EN',
                new \TYPO3\CMS\Core\Http\Uri('https://domain.org/page'),
                []
            );
            $pageArguments = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Routing\PageArguments::class, $rootPageId, 0, []);
            $nullFrontend = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\Frontend\NullFrontend::class, 'pages');
            $cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
            try {
                $cacheManager->registerCache($nullFrontend);
            } catch (\Throwable $exception) {
                unset($exception);
            }

            $context = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
            $feUserAuth = new \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication();

            $GLOBALS['TSFE'] = new \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController(
                $context,
                $site,
                $siteLanguage,
                $pageArguments
            );
            $GLOBALS['TSFE']->fe_user = $feUserAuth;
        } catch (\Throwable $e) {
            return null;
        }
        return $GLOBALS['TSFE'];
    }

    public function getLanguageService(): LanguageService
    {
        if (!($GLOBALS['LANG'] instanceof LanguageService)) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        }
        return $GLOBALS['LANG'];
    }
}
