<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class WidgetModelFactory
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @param ConfigProvider $configProvider
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ConfigProvider $configProvider, SecurityFacade $securityFacade)
    {
        $this->configProvider = $configProvider;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param Dashboard $dashboard
     * @throws InvalidConfigurationException
     * @return WidgetModel[]
     */
    public function getModels(Dashboard $dashboard)
    {
        $widgets = array();

        /**
         * @var DashboardWidget $widget
         */
        foreach ($dashboard->getWidgets() as $widget) {
            $widgetConfig = $this->configProvider->getWidgetConfig($widget->getName());
            $model = new WidgetModel($widgetConfig, $widget);
            if (!isset($widgetConfig['acl']) || $this->securityFacade->isGranted($widgetConfig['acl'])) {
                $widgets[] = $model;
            }
        }

        return $widgets;
    }
}
