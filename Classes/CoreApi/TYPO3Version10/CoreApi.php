<?php

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version10;

use BrainAppeal\T3monitor\CoreApi\AbstractCoreApi;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compatibility layer for TYPO3 v10.4
 */
class CoreApi extends AbstractCoreApi
{

    public function getLanguageService(): LanguageService
    {
        if (!($GLOBALS['LANG'] instanceof LanguageService)) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        }
        return $GLOBALS['LANG'];
    }
}
