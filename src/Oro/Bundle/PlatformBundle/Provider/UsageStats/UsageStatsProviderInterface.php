<?php

namespace Oro\Bundle\PlatformBundle\Provider\UsageStats;

/**
 * Interface for User Stats providers
 */
interface UsageStatsProviderInterface
{
    public function isApplicable(): bool;

    public function getTitle(): string;

    public function getTooltip(): ?string;

    public function getValue(): ?string;
}
