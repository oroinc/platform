<?php

namespace Oro\Bundle\PlatformBundle\Provider\UsageStats;

/**
 * Base class for Usage Stats providers
 */
abstract class AbstractUsageStatsProvider implements UsageStatsProviderInterface
{
    public function isApplicable(): bool
    {
        return true;
    }

    public function getTooltip(): ?string
    {
        return null;
    }
}
