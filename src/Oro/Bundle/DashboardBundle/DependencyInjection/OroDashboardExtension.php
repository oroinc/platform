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
            $file       = dirname($reflection->getFilename()) . '/Resources/config/dashboard.yml';
            if (is_file($file)) {
                $dashboardConfigs[] = Yaml::parse(realpath($file))['oro_dashboard_config'];
            }
        }

        foreach ($configs as $config) {
            $dashboardConfigs[] = $config;
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $dashboardConfigs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container
            ->getDefinition('oro_dashboard.manager')
            ->replaceArgument(0, $config);
        var_dump($config);
    }

    /**
     * Sorts items by a value of 'position' attribute and then remove 'position' attribute.
     * Also items which has 'visible' attribute equals false will be removed
     *
     * @param array       $items    The array to be processed
     * @param string|null $children The name of child array to be processed as well
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function prepareItems(&$items, $children = null)
    {
        uasort(
            $items,
            function (&$a, &$b) {
                $aPos = (isset($a['position']) ? $a['position'] : 9999);
                $bPos = (isset($b['position']) ? $b['position'] : 9999);
                if ($aPos == $bPos) {
                    return 0;
                }

                return ($aPos < $bPos) ? -1 : 1;
            }
        );
        foreach ($items as $key => &$item) {
            unset($item['position']);
            if (isset($item['visible']) && !$item['visible']) {
                unset($items[$key]);
                continue;
            }
            if ($children !== null) {
                if (isset($item[$children])) {
                    $this->prepareItems($item[$children], $children === 'widgets' ? 'items' : null);
                } elseif ($children === 'widgets') {
                    $item[$children] = [];
                }
            }
        }
    }

    /**
     * Sets empty 'route_parameters' attribute if it is not specified
     * Sets 'widget' parameter for 'oro_dashboard_itemized_widget' routes
     *
     * @param array $widgets
     */
    protected function prepareWidgets(&$widgets)
    {
        foreach ($widgets as $widgetName => &$widget) {
            if (!isset($widget['route_parameters'])) {
                $widget['route_parameters'] = [];
            }
            if ($widget['route'] === 'oro_dashboard_itemized_widget') {
                $widget['route_parameters']['widget'] = $widgetName;
            }
            if (isset($widget['items'])) {
                foreach ($widget['items'] as &$item) {
                    if (!isset($item['route_parameters'])) {
                        $item['route_parameters'] = [];
                    }
                }
            }
        }
    }
}
