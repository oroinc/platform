<?php

namespace Oro\Bundle\DashboardBundle\Provider;

use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;

class ConfigProvider
{
    const NODE_DASHBOARD = 'dashboards';
    const NODE_WIDGET = 'widgets';

    /**
     * Array of oro_dashboard_config config section
     *
     * @var array
     */
    protected $configs;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @param $key
     *
     * @throws \Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException
     *
     * @return array
     */
    public function getConfig($key)
    {
        if (!$this->hasConfig($key)) {
            throw new InvalidConfigurationException($key);
        }

        return $this->configs[$key];
    }

    /**
     * @return array
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * @return array
     */
    public function getDashboardConfigs()
    {
        return $this->configs[self::NODE_DASHBOARD];
    }

    /**
     * @return array
     */
    public function getWidgetConfigs()
    {
        return $this->configs[self::NODE_WIDGET];
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasConfig($key)
    {
        return isset($this->configs[$key]);
    }

    /**
     * @param $dashboardName
     * @return mixed
     * @throws InvalidConfigurationException
     */
    public function getDashboardConfig($dashboardName)
    {
        if (!$this->hasDashboardConfig($dashboardName)) {
            throw new InvalidConfigurationException($dashboardName);
        }

        return $this->configs[self::NODE_DASHBOARD][$dashboardName];
    }

    /**
     * @param $dashboardName
     * @return bool
     */
    public function hasDashboardConfig($dashboardName)
    {
        return isset($this->configs[self::NODE_DASHBOARD][$dashboardName]);
    }

    /**
     * @param $widgetName
     *
     * @throws InvalidConfigurationException
     *
     * @return mixed
     */
    public function getWidgetConfig($widgetName)
    {
        if (!$this->hasWidgetConfig($widgetName)) {
            throw new InvalidConfigurationException($widgetName);
        }

        return $this->configs[self::NODE_WIDGET][$widgetName];
    }

    /**
     * @param $widgetName
     * @return bool
     */
    public function hasWidgetConfig($widgetName)
    {
        return isset($this->configs[self::NODE_WIDGET][$widgetName]);
    }
}
