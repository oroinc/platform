<?php

namespace Oro\Bundle\DashboardBundle\Provider;

use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;

class ConfigProvider
{
    const WIDGETS_BRANCH = 'widgets';
    const DASHBOARDS_BRANCH = 'dashboards';
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
    public function getDashboardConfigs($dashboardName)
    {
        if (!$this->hasDashboardConfig($dashboardName)) {
            throw new InvalidConfigurationException($dashboardName);
        }

        return $this->configs[self::DASHBOARDS_BRANCH][$dashboardName];
    }

    /**
     * @param $dashboardName
     * @return bool
     */
    public function hasDashboardConfig($dashboardName)
    {
        return isset($this->configs[self::DASHBOARDS_BRANCH][$dashboardName]);
    }

    /**
     * @param $widgetName
     *
     * @throws InvalidConfigurationException
     *
     * @return mixed
     */
    public function getWidgetConfigs($widgetName)
    {
        if (!$this->hasWidgetConfig($widgetName)) {
            throw new InvalidConfigurationException($widgetName);
        }

        return $this->configs[self::WIDGETS_BRANCH][$widgetName];
    }

    /**
     * @param $widgetName
     * @return bool
     */
    public function hasWidgetConfig($widgetName)
    {
        return isset($this->configs[self::WIDGETS_BRANCH][$widgetName]);
    }
}
