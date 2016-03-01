<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroNavigationExtension extends Extension
{
    const TITLES_KEY              = 'oro_titles';
    const MENU_CONFIG_KEY         = 'oro_menu_config';
    const NAVIGATION_ELEMENTS_KEY = 'oro_navigation_elements';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $entitiesConfig = $titlesConfig = array();

        $configLoader = new CumulativeConfigLoader(
            'oro_navigation',
            new YamlCumulativeFileLoader('Resources/config/navigation.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            // Merge menu from bundle configuration
            if (isset($resource->data[self::MENU_CONFIG_KEY])) {
                $this->mergeMenuConfig($entitiesConfig, $resource->data[self::MENU_CONFIG_KEY]);
            }
            // Merge titles from bundle configuration
            if (!empty($resource->data[self::TITLES_KEY])) {
                $titlesConfig = array_merge($titlesConfig, (array)$resource->data[self::TITLES_KEY]);
            }
            // Merge navigation elements node from bundle configuration
            if (!empty($resource->data[self::NAVIGATION_ELEMENTS_KEY])) {
                $this->appendConfigPart(
                    $entitiesConfig[self::MENU_CONFIG_KEY],
                    $resource->data[self::NAVIGATION_ELEMENTS_KEY],
                    self::NAVIGATION_ELEMENTS_KEY
                );
            }
        }

        // Merge menu from application configuration
        if (is_array($configs)) {
            foreach ($configs as $configPart) {
                $this->mergeMenuConfig($entitiesConfig, $configPart);
            }
        }

        // process configurations to validate and merge
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $entitiesConfig);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('content_providers.yml');

        $container
            ->getDefinition('oro_menu.configuration_builder')
            ->addMethodCall('setConfiguration', array($config));
        $container
            ->getDefinition('oro_menu.twig.extension')
            ->addMethodCall('setMenuConfiguration', array($config));

        $container
            ->getDefinition('oro_navigation.title_config_reader')
            ->addMethodCall('setConfigData', array($titlesConfig));
        $container
            ->getDefinition('oro_navigation.title_provider')
            ->addMethodCall('setTitles', array($titlesConfig));
        $container
            ->getDefinition('oro_navigation.content_provider.navigation_elements')
            ->replaceArgument(0, $config[self::NAVIGATION_ELEMENTS_KEY]);

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));

        $this->addClassesToCompile(
            [
                'Oro\Bundle\NavigationBundle\Event\AddMasterRequestRouteListener',
                'Oro\Bundle\NavigationBundle\Event\RequestTitleListener'
            ]
        );
    }

    /**
     * Merge menu configuration.
     *
     * @param array $config
     * @param array $configPart
     */
    protected function mergeMenuConfig(array &$config, array &$configPart)
    {
        if (array_key_exists('tree', $configPart)) {
            foreach ($configPart['tree'] as $type => &$menuPartConfig) {
                if (isset($config[self::MENU_CONFIG_KEY]['tree'][$type])
                    && is_array($config[self::MENU_CONFIG_KEY]['tree'][$type])
                    && is_array($menuPartConfig)
                ) {
                    $this->reorganizeTree($config[self::MENU_CONFIG_KEY]['tree'][$type], $menuPartConfig);
                }
            }
        }

        $this->appendConfigPart($config, $configPart, self::MENU_CONFIG_KEY);
    }

    /**
     * Smart append of particular config into base config. Config to append will be iterated through and each node
     * will be append or merged via array_replace_recursive
     *
     * @param array  $parentConfig
     * @param array  $particularConfig
     * @param string $configBranchName Node name to append into
     *
     * @internal param array $config
     * @internal param array $configPart
     */
    protected function appendConfigPart(array &$parentConfig, array &$particularConfig, $configBranchName)
    {
        foreach ($particularConfig as $entity => $entityConfig) {
            if (isset($parentConfig[$configBranchName][$entity])) {
                $parentConfig[$configBranchName][$entity]
                    = array_replace_recursive($parentConfig[$configBranchName][$entity], $entityConfig);
            } else {
                $parentConfig[$configBranchName][$entity] = $entityConfig;
            }
        }
    }

    /**
     * @param array $config
     * @param array $configPart
     */
    protected function reorganizeTree(array &$config, array &$configPart)
    {
        if (!empty($configPart['children'])) {
            foreach ($configPart['children'] as $childName => &$childConfig) {
                if (isset($childConfig['merge_strategy']) && $childConfig['merge_strategy'] != 'append') {
                    if (isset($childConfig['merge_strategy']) && $childConfig['merge_strategy'] == 'move') {
                        $existingItem = $this->getMenuItemByName($config, $childName);
                        if (!empty($existingItem['children'])) {
                            $childChildren = isset($childConfig['children']) ? $childConfig['children'] : array();
                            $childConfig['children']
                                           = array_merge($existingItem['children'], $childChildren);
                        }
                    }
                    $this->removeItem($config, $childName);
                } elseif (is_array($childConfig)) {
                    $this->reorganizeTree($config, $childConfig);
                }
            }
        }
    }

    /**
     * @param array  $config
     * @param string $childName
     */
    protected function removeItem(array &$config, $childName)
    {
        if (!empty($config['children'])) {
            foreach ($config['children'] as $key => &$configRow) {
                if ($key === $childName) {
                    unset($config['children'][$childName]);
                } elseif (is_array($configRow)) {
                    $this->removeItem($configRow, $childName);
                }
            }
        }
    }

    /**
     * @param array $config
     * @param       $childName
     *
     * @return array|null
     */
    protected function getMenuItemByName(array $config, $childName)
    {
        if (!empty($config['children'])) {
            foreach ($config['children'] as $key => $configRow) {
                if ($key === $childName) {
                    return $config['children'][$childName];
                } elseif (is_array($configRow)) {
                    return $this->getMenuItemByName($configRow, $childName);
                }
            }
        }

        return null;
    }
}
