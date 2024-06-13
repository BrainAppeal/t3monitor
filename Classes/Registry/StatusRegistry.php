<?php

declare(strict_types=1);


namespace BrainAppeal\T3monitor\Registry;

use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Registry for status providers. The registry receives all services, tagged with "reports.status".
 * The tagging of status providers is automatically done based on the implemented StatusProviderInterface.
 */
class StatusRegistry
{
    private \TYPO3\CMS\Reports\Registry\StatusRegistry $registry;

    public function __construct()
    {
    }

    /**
     * @param \TYPO3\CMS\Reports\Registry\StatusRegistry $registry
     * @return void
     */
    public function setRegistry(\TYPO3\CMS\Reports\Registry\StatusRegistry $registry) {
        $this->registry = $registry;
    }

    /**
     * Get all registered status providers
     *
     * @return StatusProviderInterface[]
     */
    public function getProviders(): array
    {
        if (null === $this->registry) {
            return [];
        }
        return $this->registry->getProviders();
    }
}
