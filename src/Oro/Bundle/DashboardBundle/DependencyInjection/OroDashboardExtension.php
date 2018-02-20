<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroDashboardExtension extends Extension
{
    const CONFIG_ROOT_NODE = 'dashboards';
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $dashboardConfigs = array();

        $configLoader = new CumulativeConfigLoader(
            'oro_dashboard',
            new YamlCumulativeFileLoader('Resources/config/oro/dashboards.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $dashboardConfigs[] = $resource->data[self::CONFIG_ROOT_NODE];
        }

        foreach ($configs as $config) {
            $dashboardConfigs[] = $config;
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $dashboardConfigs);
        $this->prepareWidgets($config['widgets'], $config['widgets_configuration']);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->getDefinition('oro_dashboard.config_provider')->replaceArgument(0, $config);
    }

    /**
     * Sets "widget" parameter for all widget routes and sort widget items
     *
     * @param array $widgets
     * @param array $defaultConfiguration
     */
    protected function prepareWidgets(array &$widgets, array $defaultConfiguration)
    {
        foreach ($widgets as $widgetName => &$widget) {
            $widget['configuration'] = array_merge_recursive($defaultConfiguration, $widget['configuration']);
            $widget['route_parameters']['widget'] = $widgetName;
            if (!empty($widget['items'])) {
                $this->sortItemsByPosition($widget['items']);
            } else {
                unset($widget['items']);
            }
        }
    }

    /**
     * Sorts items by a value of "position" attribute
     *
     * @param array $items The array to be processed
     */
    protected function sortItemsByPosition(array &$items)
    {
        ArrayUtil::sortBy($items, false, 'position');

        foreach ($items as &$item) {
            unset($item['position']);
        }
    }
}
