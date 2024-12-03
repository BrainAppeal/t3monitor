<?php

namespace BrainAppeal\T3monitor\CoreApi\TYPO3Version13;

use BrainAppeal\T3monitor\CoreApi\TYPO3Version12\CoreApi as CoreApiV12;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Compatibility layer for TYPO3 v13.4
 */
class CoreApi extends CoreApiV12
{

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
        // Causes exception if not initialized: Setup array has not been initialized.
        // This happens in cached Frontend scope where full TypoScript is not needed by the system.
        // @see \TYPO3\CMS\Extensionmanager\Utility\ListUtility::getExtensionTerData
        if (class_exists(FrontendTypoScript::class) && !$request->getAttribute('frontend.typoscript')) {
            /** @var FrontendTypoScript $frontendTypoScript */
            $frontendTypoScript = GeneralUtility::makeInstance(
                FrontendTypoScript::class,
                GeneralUtility::makeInstance(RootNode::class),
                [],
                [],
                []
            );
            $frontendTypoScript->setSetupArray([]);
            $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        }
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }
}
