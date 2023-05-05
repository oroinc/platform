<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\DashboardType;

use Oro\Bundle\DashboardBundle\DashboardType\DashboardTypeConfigProviderInterface;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;

class DashboardTestType implements DashboardTypeConfigProviderInterface
{
    public function isSupported(?string $dashboardType): bool
    {
        return $dashboardType === 'test';
    }

    public function getConfig(Dashboard $dashboard): array
    {
        return ['template' => 'test'];
    }
}
