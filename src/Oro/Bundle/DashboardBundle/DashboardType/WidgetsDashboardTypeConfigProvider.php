<?php

namespace Oro\Bundle\DashboardBundle\DashboardType;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Defines widgets dashboard type.
 */
class WidgetsDashboardTypeConfigProvider implements DashboardTypeConfigProviderInterface
{
    public const TYPE_NAME = 'widgets';

    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function isSupported(?string $dashboardType): bool
    {
        return null === $dashboardType || self::TYPE_NAME === ExtendHelper::getEnumInternalId($dashboardType);
    }

    #[\Override]
    public function getConfig(Dashboard $dashboard): array
    {
        $dashboardName = $dashboard->getName();
        if (!empty($dashboardName)) {
            $dashboardConfig = $this->configProvider->getDashboardConfig($dashboardName);
        } else {
            $dashboardConfig = array();
        }

        return $dashboardConfig;
    }
}
