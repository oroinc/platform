<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Event\WidgetConfigurationLoadEvent;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The provider for dashboards configuration
 * that is loaded from "Resources/config/oro/dashboards.yml" files.
 */
class ConfigProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/dashboards.yml';

    private const DASHBOARDS     = 'dashboards';
    private const WIDGETS        = 'widgets';
    private const WIDGETS_CONFIG = 'widgets_configuration';
    private const CONFIG         = 'configuration';
    private const WIDGET         = 'widget';
    private const ROUTE_PARAMS   = 'route_parameters';
    private const ITEMS          = 'items';
    private const POSITION       = 'position';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        string $cacheFile,
        bool $debug,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($cacheFile, $debug);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getDashboardConfigs(): array
    {
        $configs = $this->doGetConfig();

        return $configs[self::DASHBOARDS];
    }

    public function hasDashboardConfig(string $dashboardName): bool
    {
        $configs = $this->doGetConfig();

        return isset($configs[self::DASHBOARDS][$dashboardName]);
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getDashboardConfig(string $dashboardName): array
    {
        $configs = $this->doGetConfig();
        if (!isset($configs[self::DASHBOARDS][$dashboardName])) {
            throw new InvalidConfigurationException($dashboardName);
        }

        return $configs[self::DASHBOARDS][$dashboardName];
    }

    public function getWidgetConfigs(): array
    {
        $configs = $this->doGetConfig();

        return $configs[self::WIDGETS];
    }

    public function hasWidgetConfig(string $widgetName): bool
    {
        $configs = $this->doGetConfig();

        return isset($configs[self::WIDGETS][$widgetName]);
    }

    /**
     * @throws InvalidConfigurationException if the widget config was not found and $throwExceptionIfMissing = true
     */
    public function getWidgetConfig(string $widgetName, bool $throwExceptionIfMissing = true): ?array
    {
        $configs = $this->doGetConfig();
        if (!isset($configs[self::WIDGETS][$widgetName])) {
            if ($throwExceptionIfMissing) {
                throw new InvalidConfigurationException($widgetName);
            }
            return null;
        }

        $widgetConfig = $configs[self::WIDGETS][$widgetName];
        if ($this->eventDispatcher->hasListeners(WidgetConfigurationLoadEvent::EVENT_NAME)) {
            $event = new WidgetConfigurationLoadEvent($widgetConfig);
            $this->eventDispatcher->dispatch($event, WidgetConfigurationLoadEvent::EVENT_NAME);
            $widgetConfig = $event->getConfiguration();
        }

        return $widgetConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_dashboard', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (!empty($resource->data[Configuration::ROOT_NODE_NAME])) {
                $configs[] = $resource->data[Configuration::ROOT_NODE_NAME];
            }
        }

        $processedConfig = CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new Configuration(),
            $configs
        );

        $this->prepareWidgets(
            $processedConfig[self::WIDGETS],
            $processedConfig[self::WIDGETS_CONFIG]
        );
        unset($processedConfig[self::WIDGETS_CONFIG]);

        return $processedConfig;
    }

    private function prepareWidgets(array &$widgets, array $defaultConfig): void
    {
        foreach ($widgets as $widgetName => &$widget) {
            $widget[self::CONFIG] = \array_merge_recursive($defaultConfig, $widget[self::CONFIG]);
            $widget[self::ROUTE_PARAMS][self::WIDGET] = $widgetName;
            if (empty($widget[self::ITEMS])) {
                unset($widget[self::ITEMS]);
            } else {
                $this->sortItemsByPosition($widget[self::ITEMS]);
            }
        }
    }

    private function sortItemsByPosition(array &$items): void
    {
        ArrayUtil::sortBy($items, false, self::POSITION);
        foreach ($items as &$item) {
            unset($item[self::POSITION]);
        }
    }
}
