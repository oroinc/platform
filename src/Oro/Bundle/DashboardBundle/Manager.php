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
        $this->config         = $config;
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
                unset($widget['items']);
                $result[$widgetName] = $widget;
            } else {
                unset($result[$widgetName]);
            }
        }

        return $result;
    }

    /**
     * Returns widget attributes
     *
     * @param string $widgetName The name of widget
     * @return array
     */
    public function getWidgetAttributes($widgetName)
    {
        $widget = $this->config['widgets'][$widgetName];
        unset($widget['route']);
        unset($widget['route_parameters']);
        unset($widget['acl']);
        unset($widget['items']);

        return $widget;
    }

    /**
     * Returns widget attributes with attribute name converted to use in widget's TWIG template
     *
     * @param string $widgetName The name of widget
     * @return array
     */
    public function getWidgetAttributesForTwig($widgetName)
    {
        $result = [
            'widgetName' => $widgetName
        ];
        foreach ($this->getWidgetAttributes($widgetName) as $key => $val) {
            $attrName = 'widget';
            foreach (explode('_', str_replace('-', '_', $key)) as $keyPart) {
                $attrName .= ucfirst($keyPart);
            }
            $result[$attrName] = $val;
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
        $items = isset($this->config['widgets'][$widgetName]['items'])
            ? $this->config['widgets'][$widgetName]['items']
            : [];
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
