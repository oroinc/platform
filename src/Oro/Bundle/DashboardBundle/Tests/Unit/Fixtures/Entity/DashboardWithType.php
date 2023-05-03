<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\EntityExtendBundle\Model\EnumValue;

class DashboardWithType extends Dashboard
{
    private EnumValue $dashboardType;

    public function getDashboardType(): EnumValue
    {
        return $this->dashboardType;
    }

    public function setDashboardType(EnumValue $dashboardType): void
    {
        $this->dashboardType = $dashboardType;
    }
}
