<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;

class DashboardModelFactory
{
    /**
     * @var WidgetModelFactory
     */
    protected $widgetModelFactory;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    public function __construct(WidgetModelFactory $widgetModelFactory, ConfigProvider $configProvider)
    {
        $this->widgetModelFactory = $widgetModelFactory;
        $this->configProvider = $configProvider;
    }

    /**
     * Returns all widgets for the given dashboard
     *
     * @param Dashboard $dashboard
     * @return DashboardModel
     */
    public function getDashboardModel(Dashboard $dashboard)
    {
        if ($this->configProvider->hasDashboardConfig($dashboard->getName())) {
            $dashboardConfig = $this->configProvider->getDashboardConfig($dashboard->getName());
        } else {
            $dashboardConfig = array();
        }

        $widgetsCollection = new WidgetsModelCollection($dashboard, $this->widgetModelFactory);

        return new DashboardModel($widgetsCollection, $dashboardConfig, $dashboard);
    }
}
