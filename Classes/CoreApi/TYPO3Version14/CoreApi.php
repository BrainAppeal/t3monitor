<?php

declare(strict_types=1);

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version14;

use BrainAppeal\T3monitor\CoreApi\TYPO3Version13\CoreApi as CoreApiV13;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\UpgradeWizardsService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Report\InstallStatusReport;

/**
 * Compatibility layer for TYPO3 v14.3
 */
class CoreApi extends CoreApiV13
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
        if ($className === InstallStatusReport::class && class_exists(UpgradeWizardsService::class)) {
            $upgradeWizardsService = GeneralUtility::makeInstance(UpgradeWizardsService::class);
            return GeneralUtility::makeInstance($className, $upgradeWizardsService);
        }
        return GeneralUtility::makeInstance($className, ...$constructorArguments);
    }

    protected function initializePageRenderer(ServerRequestInterface $request, array &$expressionMatcherVariables): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $language = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
        if ($language->hasCustomTypo3Language()) {
            $locale = GeneralUtility::makeInstance(Locales::class)->createLocale($language->getTypo3Language());
        } else {
            $locale = $language->getLocale();
        }
        $pageRenderer->setLanguage($locale, $request);
    }
}
