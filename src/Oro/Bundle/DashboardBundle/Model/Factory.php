<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;

class Factory
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Get dashboard model from dashboard entity
     *
     * @param Dashboard $dashboard
     * @return DashboardModel
     */
    public function createDashboardModel(Dashboard $dashboard)
    {
        $dashboardName = $dashboard->getName();
        if (!empty($dashboardName) && $this->configProvider->hasDashboardConfig($dashboardName)) {
            $dashboardConfig = $this->configProvider->getDashboardConfig($dashboardName);
        } else {
            $dashboardConfig = array();
        }

        return new DashboardModel(
            $dashboard,
            $this->createWidgetCollection($dashboard),
            $dashboardConfig
        );
    }

    /**
     * @param Widget $widget
     * @return WidgetModel
     */
    public function createWidgetModel(Widget $widget)
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widget->getName());

        return new WidgetModel($widget, $widgetConfig);
    }

    /**
     * @param Dashboard $dashboard
     * @return WidgetCollection
     */
    public function createWidgetCollection(Dashboard $dashboard)
    {
        return new WidgetCollection($dashboard, $this);
    }
}
