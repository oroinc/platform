<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\DashboardBundle\DashboardType\CloneableDashboardTypeInterface;
use Oro\Bundle\DashboardBundle\DashboardType\DashboardTypeConfigProviderInterface;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;

/**
 * Stub for testing dashboard type provider that implements CloneableDashboardTypeInterface.
 */
class CloneableDashboardTypeConfigProviderStub implements
    DashboardTypeConfigProviderInterface,
    CloneableDashboardTypeInterface
{
    public function __construct(
        private string $supportedType,
        private bool $isCloneable = false
    ) {
    }

    public function isSupported(?string $dashboardType): bool
    {
        return $dashboardType === $this->supportedType;
    }

    public function getConfig(Dashboard $dashboard): array
    {
        return [];
    }

    public function isCloneable(): bool
    {
        return $this->isCloneable;
    }
}
