<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroDashboardExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $dashboardConfigs = array();

        $configLoader = new CumulativeConfigLoader(
            'oro_dashboard',
            new YamlCumulativeFileLoader('Resources/config/dashboard.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $dashboardConfigs[] = $resource->data['oro_dashboard_config'];
        }

        foreach ($configs as $config) {
            $dashboardConfigs[] = $config;
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $dashboardConfigs);
        $this->prepareWidgets($config['widgets']);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->getDefinition('oro_dashboard.config_provider')->replaceArgument(0, $config);
    }

    /**
     * Sets "widget" parameter for all widget routes and sort widget items
     *
     * @param array $widgets
     */
    protected function prepareWidgets(array &$widgets)
    {
        foreach ($widgets as $widgetName => &$widget) {
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
        // Update "position" attribute if it was not specified to keep order of such items as it was declared in config
        $lastUnspecifiedPosition = Configuration::UNSPECIFIED_POSITION;
        foreach ($items as &$item) {
            if ($item['position'] === Configuration::UNSPECIFIED_POSITION) {
                $lastUnspecifiedPosition++;
                $item['position'] = $lastUnspecifiedPosition;
            }
        }

        // Sort items
        uasort(
            $items,
            function (&$first, &$second) {
                if ($first['position'] == $second['position']) {
                    return 0;
                } else {
                    return ($first['position'] < $second['position']) ? -1 : 1;
                }
            }
        );

        foreach ($items as &$item) {
            unset($item['position']);
        }
    }
}
