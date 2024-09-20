<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

/**
 * Load dashboard type enum options data.
 */
class LoadDashboardTypeOptionsData extends AbstractEnumFixture
{
    protected function getData(): array
    {
        return [
            'widgets' => 'Widgets'
        ];
    }

    protected function getDefaultValue(): string
    {
        return 'widgets';
    }

    protected function getEnumCode(): string
    {
        return 'dashboard_type';
    }
}
