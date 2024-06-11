<?php

namespace Oro\Bundle\PlatformBundle\Provider\UsageStats;

/**
 * Registry for all Usage Stats providers
 */
class UsageStatsProviderRegistry
{
    /** @var UsageStatsProviderInterface[] */
    private array $providers = [];

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @return UsageStatsProviderInterface[]
     */
    public function getProviders(): array
    {
        return array_filter(
            $this->providers,
            static fn ($provider) => $provider->isApplicable()
        );
    }
}
