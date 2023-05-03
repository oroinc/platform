<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\DashboardType\DashboardTypeConfigProviderInterface;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;

/**
 * Dashboard model factory.
 */
class Factory
{
    private ConfigProvider $configProvider;
    private StateManager $stateManager;
    private WidgetConfigs $widgetConfigs;

    /** @var iterable|DashboardTypeConfigProviderInterface[]  */
    private iterable $dashboardTypeConfigProviders;

    public function __construct(
        ConfigProvider $configProvider,
        StateManager $stateManager,
        WidgetConfigs $widgetConfigs,
        iterable $dashboardTypeConfigProviders
    ) {
        $this->configProvider = $configProvider;
        $this->stateManager   = $stateManager;
        $this->widgetConfigs  = $widgetConfigs;
        $this->dashboardTypeConfigProviders = $dashboardTypeConfigProviders;
    }

    /**
     * Get dashboard model from dashboard entity.
     */
    public function createDashboardModel(Dashboard $dashboard): DashboardModel
    {
        $dashboardConfig = [];
        $dashboardType = $dashboard->getDashboardType()?->getId();
        foreach ($this->dashboardTypeConfigProviders as $dashboardTypeConfigProvider) {
            if ($dashboardTypeConfigProvider->isSupported($dashboardType)) {
                $dashboardConfig = $dashboardTypeConfigProvider->getConfig($dashboard);
                break;
            }
        }

        return new DashboardModel(
            $dashboard,
            $this->createWidgetCollection($dashboard),
            $dashboardConfig
        );
    }

    public function createWidgetModel(Widget $widget): WidgetModel
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widget->getName());
        $widgetState  = $this->stateManager->getWidgetState($widget);

        return new WidgetModel($widget, $widgetConfig, $widgetState);
    }

    public function createVisibleWidgetModel(Widget $widget): ?WidgetModel
    {
        $widgetConfig = $this->widgetConfigs->getWidgetConfig($widget->getName());
        if (!$widgetConfig) {
            return null;
        }

        $widgetState  = $this->stateManager->getWidgetState($widget);

        return new WidgetModel($widget, $widgetConfig, $widgetState);
    }

    public function createWidgetCollection(Dashboard $dashboard): WidgetCollection
    {
        return new WidgetCollection($dashboard, $this);
    }
}
