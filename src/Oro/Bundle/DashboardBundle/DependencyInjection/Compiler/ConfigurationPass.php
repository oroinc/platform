<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class ConfigurationPass implements CompilerPassInterface
{
    const MANAGER_SERVICE_ID    = 'oro_dashboard.manager';
    const CONFIG_FILE_NAME      = 'dashboard.yml';
    const CONFIG_ROOT_NODE_NAME = 'oro_dashboard_config';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::MANAGER_SERVICE_ID)) {
            $config = array();
            foreach ($container->getParameter('kernel.bundles') as $bundle) {
                $reflection = new \ReflectionClass($bundle);
                $file       = dirname($reflection->getFilename()) . '/Resources/config/' . self::CONFIG_FILE_NAME;
                if (is_file($file)) {
                    $config = array_merge_recursive(
                        $config,
                        Yaml::parse(realpath($file))[self::CONFIG_ROOT_NODE_NAME]
                    );
                }
            }

            // sort dashboards, widgets and widget items (if any)
            // remove 'position' attribute after sorting
            // remove non visible items
            $this->prepareItems($config['dashboards'], 'widgets');

            // set empty 'route_parameters' attribute if it is not specified
            // set 'widget' parameter for 'oro_dashboard_itemized_widget' routes
            $this->prepareWidgets($config['widgets']);

            $managerDef = $container->getDefinition(self::MANAGER_SERVICE_ID);
            $managerDef->replaceArgument(0, $config);
        }
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
            if ($children !== null && isset($item[$children])) {
                $this->prepareItems($item[$children], $children === 'widgets' ? 'items' : null);
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
