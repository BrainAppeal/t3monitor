<?php

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version12;

use BrainAppeal\T3monitor\CoreApi\AbstractCoreApi;
use BrainAppeal\T3monitor\CoreApi\TYPO3Version12\Reports\Security;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Report\InstallStatusReport;

/**
 * Compatibility layer for TYPO3 v12.4
 */
class CoreApi extends AbstractCoreApi
{
    protected function getAvailableReportsClassMap(): array
    {
        $classMap = parent::getAvailableReportsClassMap();
        $classMap['security'] = Security::class;
        return $classMap;
    }

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
}
