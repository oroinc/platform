<?php

namespace Oro\Bundle\PlatformBundle\Provider\UsageStats;

/**
 * Base class for Usage Stats providers
 */
abstract class AbstractUsageStatsProvider implements UsageStatsProviderInterface
{
    #[\Override]
    public function isApplicable(): bool
    {
        return true;
    }

    #[\Override]
    public function getTooltip(): ?string
    {
        return null;
    }
}
