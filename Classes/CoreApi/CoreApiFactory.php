<?php

namespace BrainAppeal\T3monitor\CoreApi;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Create instance of compatibility helper service depending on current TYPO3 version
 */
class CoreApiFactory implements SingletonInterface {

    /**
     * @var ?CoreApiInterface
     */
    protected $instance;

    /**
     * Get 'major version' from installed version of TYPO3, e.g., '10' from '10.4.30'
     *
     * @return int For example 10
     */
    protected static function getInstalledMajorVersion(): int
    {
        return (string)GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
    }

    /**
     * @return \BrainAppeal\T3monitor\CoreApi\CoreApiInterface
     */
    public function getCoreApi(): CoreApiInterface
    {
        if (null === $this->instance) {
            $majorVersion = self::getInstalledMajorVersion();
            switch ($majorVersion) {
                case 10:
                    $this->instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\T3monitor\CoreApi\TYPO3Version10\CoreApi::class);
                    break;
                case 11:
                    $this->instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\T3monitor\CoreApi\TYPO3Version11\CoreApi::class);
                    break;
                case 12:
                    $this->instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\T3monitor\CoreApi\TYPO3Version12\CoreApi::class);
                    break;

            }
        }
        return $this->instance;
    }
}
