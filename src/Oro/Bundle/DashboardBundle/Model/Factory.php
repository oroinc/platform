<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Component\Config\Resolver\ResolverInterface;

class Factory
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var StateManager */
    protected $stateManager;

    /** @var ResolverInterface */
    protected $resolver;

    /**
     * @param ConfigProvider    $configProvider
     * @param StateManager      $stateManager
     * @param ResolverInterface $resolver
     */
    public function __construct(
        ConfigProvider $configProvider,
        StateManager $stateManager,
        ResolverInterface $resolver
    ) {
        $this->configProvider = $configProvider;
        $this->stateManager   = $stateManager;
        $this->resolver       = $resolver;
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
        $widgetConfig = $this->configProvider->getWidgetConfig($widget->getName());
        $widgetState  = $this->stateManager->getWidgetState($widget);
        if (isset($widgetConfig['applicable'])) {
            $resolved   = $this->resolver->resolve([$widgetConfig['applicable']]);
            $applicable = reset($resolved);
            if (!$applicable) {
                return null;
            }
        }

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
