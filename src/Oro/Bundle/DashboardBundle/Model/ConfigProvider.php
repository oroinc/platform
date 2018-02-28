<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Event\WidgetConfigurationLoadEvent;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigProvider
{
    const NODE_DASHBOARD = 'dashboards';
    const NODE_WIDGET = 'widgets';

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Array of dashboards config section
     *
     * @var array
     */
    protected $configs;

    /**
     * @param array $configs
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(array $configs, EventDispatcherInterface $eventDispatcher)
    {
        $this->configs = $configs;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $key
     * @throws InvalidConfigurationException
     * @return array
     */
    public function getConfig($key)
    {
        if (!$this->hasConfig($key)) {
            throw new InvalidConfigurationException($key);
        }

        return $this->copyConfigurationArray($this->configs[$key]);
    }

    /**
     * @return array
     */
    public function getConfigs()
    {
        return $this->copyConfigurationArray($this->configs);
    }

    /**
     * @return array
     */
    public function getDashboardConfigs()
    {
        return $this->copyConfigurationArray($this->configs[self::NODE_DASHBOARD]);
    }

    /**
     * @return array
     */
    public function getWidgetConfigs()
    {
        return $this->copyConfigurationArray($this->configs[self::NODE_WIDGET]);
    }

    /**
     * @param string $dashboardName
     * @throws InvalidConfigurationException
     * @return array
     */
    public function getDashboardConfig($dashboardName)
    {
        if (!$this->hasDashboardConfig($dashboardName)) {
            throw new InvalidConfigurationException($dashboardName);
        }

        return $this->copyConfigurationArray($this->configs[self::NODE_DASHBOARD][$dashboardName]);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasConfig($key)
    {
        return isset($this->configs[$key]);
    }

    /**
     * @param string $dashboardName
     * @return bool
     */
    public function hasDashboardConfig($dashboardName)
    {
        return isset($this->configs[self::NODE_DASHBOARD][$dashboardName]);
    }

    /**
     * @param string $widgetName
     * @throws InvalidConfigurationException
     * @return array
     */
    public function getWidgetConfig($widgetName)
    {
        if (!$this->hasWidgetConfig($widgetName)) {
            throw new InvalidConfigurationException($widgetName);
        }

        $config = $this->copyConfigurationArray($this->configs[self::NODE_WIDGET][$widgetName]);
        if ($this->eventDispatcher->hasListeners(WidgetConfigurationLoadEvent::EVENT_NAME)) {
            $event = new WidgetConfigurationLoadEvent($config);
            $this->eventDispatcher->dispatch(WidgetConfigurationLoadEvent::EVENT_NAME, $event);
            $config = $event->getConfiguration();
        }

        return $config;
    }

    /**
     * @param string $widgetName
     * @return bool
     */
    public function hasWidgetConfig($widgetName)
    {
        return isset($this->configs[self::NODE_WIDGET][$widgetName]);
    }

    /**
     * Copy array to avoid rewrite original array items by reference
     *
     * @param array $configurations
     * @return array
     */
    protected function copyConfigurationArray(array $configurations)
    {
        return array_map(function ($config) {
            if (!is_array($config)) {
                return $config;
            }

            return $this->copyConfigurationArray($config);
        }, $configurations);
    }
}
