<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

class OroDashboardExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $dashboardConfigs = array();
        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $file       = realpath(dirname($reflection->getFilename()) . '/Resources/config/dashboard.yml');
            if (is_file($file)) {
                $dashboardConfigs[] = Yaml::parse($file)['oro_dashboard_config'];
            }
        }

        $dashboardConfigs = array_merge($dashboardConfigs, $configs);

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $dashboardConfigs);
        // sort dashboards and widgets
        // remove 'position' attribute after sorting
        // remove non visible dashboards and widgets
        // remove 'visible' attribute for rest dashboards and widgets
        $this->prepareItems($config['dashboards']);
        // set 'widget' parameter for all widget routes
        // sort widget items (if any)
        // remove 'position' attribute after sorting
        // remove non visible items
        // remove 'visible' attribute for rest items
        // remove empty 'items' attribute
        $this->prepareWidgets($config['widgets']);
        // remove dashboards which have no widgets
        foreach ($config['dashboards'] as $dashboardName => &$dashboard) {
            if (empty($dashboard['widgets'])) {
                unset($config['dashboards'][$dashboardName]);
            }
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->getDefinition('oro_dashboard.manager')->replaceArgument(0, $config);
    }

    /**
     * Sorts items by a value of 'position' attribute and then remove 'position' attribute.
     * Removes items which has 'visible' attribute equals false and then remove 'visible' attribute for rest items.
     *
     * @param array       $items    The array to be processed
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function prepareItems(&$items)
    {
        // update 'position' attribute if it was not specified to keep order of such items as it was declared in config
        $lastUnspecifiedPosition = Configuration::UNSPECIFIED_POSITION;
        foreach ($items as &$item) {
            if ($item['position'] === Configuration::UNSPECIFIED_POSITION) {
                $item['position'] = ++$lastUnspecifiedPosition;
            }
        }
        // sort items
        uasort(
            $items,
            function ($first, $second) {
                return $first['position'] - $second['position'];
            }
        );
        // remove non visible items and remove 'position' and 'visible' attributes
        foreach ($items as $key => &$item) {
            unset($item['position']);
            if (isset($item['visible'])) {
                if (!$item['visible']) {
                    unset($items[$key]);
                    continue;
                } else {
                    unset($item['visible']);
                }
            }
            if (isset($item['widgets'])) {
                $this->prepareItems($item['widgets']);
            }
        }
    }

    /**
     * Sets 'widget' parameter for all widget routes
     * Sorts items by a value of 'position' attribute and then remove 'position' attribute.
     * Removes items which has 'visible' attribute equals false and then remove 'visible' attribute for rest items.
     * Removes empty 'items' attribute.
     *
     * @param array $widgets
     */
    protected function prepareWidgets(&$widgets)
    {
        foreach ($widgets as $widgetName => &$widget) {
            $widget['route_parameters']['widget'] = $widgetName;
            if (isset($widget['items'])) {
                $this->prepareItems($widget['items']);
                if (empty($widget['items'])) {
                    unset($widget['items']);
                }
            }
        }
    }
}
