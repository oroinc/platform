<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;

class Factory
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var StateManager */
    protected $stateManager;

    /** @var WidgetConfigs */
    protected $widgetConfigs;

    /**
     * @param ConfigProvider    $configProvider
     * @param StateManager      $stateManager
     * @param WidgetConfigs     $widgetConfigs
     */
    public function __construct(
        ConfigProvider $configProvider,
        StateManager $stateManager,
        WidgetConfigs $widgetConfigs
    ) {
        $this->configProvider = $configProvider;
        $this->stateManager   = $stateManager;
        $this->widgetConfigs  = $widgetConfigs;
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
        if (!empty($dashboardName)) {
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
        $widgetState  = $this->stateManager->getWidgetState($widget);

        return new WidgetModel($widget, $widgetConfig, $widgetState);
    }

    /**
     * @param Widget $widget
     * @return WidgetModel|null
     */
    public function createVisibleWidgetModel(Widget $widget)
    {
        $widgetConfig = $this->widgetConfigs->getWidgetConfig($widget->getName());
        if (!$widgetConfig) {
            return null;
        }

        $widgetState  = $this->stateManager->getWidgetState($widget);

        return new WidgetModel($widget, $widgetConfig, $widgetState);
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
