<?php

namespace Oro\Bundle\DashboardBundle;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class Manager
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * Constructor
     *
     * @param array          $config
     * @param SecurityFacade $securityFacade
     */
    public function __construct(array $config, SecurityFacade $securityFacade)
    {
        $this->config = $config;
        $this->securityFacade = $securityFacade;
    }

    /**
     * Returns name of default dashboard
     *
     * @return string
     */
    public function getDefaultDashboardName()
    {
        return $this->config['default_dashboard'];
    }

    /**
     * Returns all dashboards
     *
     * @return array
     *      key = dashboard name
     *      value = dashboard label name
     */
    public function getDashboards()
    {
        $result = [];
        foreach ($this->config['dashboards'] as $name => &$dashboard) {
            $result[$name] = $dashboard['label'];
        }

        return $result;
    }

    /**
     * Returns dashboard configuration
     *
     * @param string $name The name of dashboard
     * @return array
     */
    public function getDashboard($name)
    {
        $result = $this->config['dashboards'][$name];
        unset($result['widgets']);

        return $result;
    }

    /**
     * Returns all widgets for the given dashboard
     *
     * @param string $name The name of dashboard
     * @return array
     */
    public function getDashboardWidgets($name)
    {
        $result = $this->config['dashboards'][$name]['widgets'];
        foreach (array_keys($result) as $widgetName) {
            $widget = array_merge_recursive($result[$widgetName], $this->config['widgets'][$widgetName]);
            if (!isset($widget['acl']) || $this->securityFacade->isGranted($widget['acl'])) {
                unset($widget['acl']);
                $result[$widgetName] = $widget;
            } else {
                unset($result[$widgetName]);
            }
        }

        return $result;
    }

    /**
     * Returns a list of items for the given widget
     *
     * @param string $widgetName The name of widget
     * @return array
     */
    public function getWidgetItems($widgetName)
    {
        $items = $this->config['widgets'][$widgetName]['items'];
        foreach ($items as $itemName => &$item) {
            if (!isset($item['acl']) || $this->securityFacade->isGranted($item['acl'])) {
                unset($item['acl']);
            } else {
                unset($items[$itemName]);
            }
        }

        return $items;
    }
}
