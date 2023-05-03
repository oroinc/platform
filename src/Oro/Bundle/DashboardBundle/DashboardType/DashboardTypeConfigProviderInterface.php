<?php

namespace Oro\Bundle\DashboardBundle\DashboardType;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;

/**
 * Interface of the dashboard config provider for dashboard type.
 */
interface DashboardTypeConfigProviderInterface
{
    public function isSupported(?string $dashboardType): bool;
    public function getConfig(Dashboard $dashboard): array;
}
