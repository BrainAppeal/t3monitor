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
    /**
     * @var StatusProviderInterface[]
     */
    private array $providers = [];

    /**
     * @param iterable<StatusProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $item) {
            $this->providers[] = $item;
        }
    }

    /**
     * Get all registered status providers
     *
     * @return StatusProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
