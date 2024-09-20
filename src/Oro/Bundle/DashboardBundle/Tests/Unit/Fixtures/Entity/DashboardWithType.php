<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\EntityExtendBundle\Model\EnumOption;

class DashboardWithType extends Dashboard
{
    private EnumOption $dashboardType;

    public function getDashboardType(): EnumOption
    {
        return $this->dashboardType;
    }

    public function setDashboardType(EnumOption $dashboardType): void
    {
        $this->dashboardType = $dashboardType;
    }
}
