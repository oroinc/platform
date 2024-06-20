<?php

namespace Oro\Bundle\PlatformBundle\Provider\UsageStats;

use Oro\Bundle\PlatformBundle\Model\UsageStat;

/**
 * Usage Stats provider for the System Information page
 */
class UsageStatsProvider
{
    private UsageStatsProviderRegistry $usageStatsProviderRegistry;

    public function __construct(UsageStatsProviderRegistry $usageStatsProviderRegistry)
    {
        $this->usageStatsProviderRegistry = $usageStatsProviderRegistry;
    }

    /**
     * @return UsageStat[]
     */
    public function getUsageStats(): array
    {
        $usageStats = [];

        foreach ($this->usageStatsProviderRegistry->getProviders() as $provider) {
            $usageStats[] = UsageStat::create(
                $provider->getTitle(),
                $provider->getTooltip(),
                $provider->getValue()
            );
        }

        return $usageStats;
    }
}
