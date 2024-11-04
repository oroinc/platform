<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\DashboardType;

use Oro\Bundle\DashboardBundle\DashboardType\DashboardTypeConfigProviderInterface;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;

class DashboardTestType implements DashboardTypeConfigProviderInterface
{
    #[\Override]
    public function isSupported(?string $dashboardType): bool
    {
        return $dashboardType === 'test';
    }

    #[\Override]
    public function getConfig(Dashboard $dashboard): array
    {
        return ['template' => 'test'];
    }
}
