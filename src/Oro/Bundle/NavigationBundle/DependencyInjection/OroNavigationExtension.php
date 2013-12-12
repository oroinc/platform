<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroNavigationExtension extends Extension
{
    const MENU_CONFIG_KEY = 'oro_menu_config';
    const TITLES_KEY = 'oro_titles';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $entitiesConfig = array();
        $titlesConfig = array();

        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            if (is_file($file = dirname($reflection->getFilename()) . '/Resources/config/navigation.yml')) {
                $bundleConfig = Yaml::parse(realpath($file));

                // Merge menu from bundle configuration
                if (isset($bundleConfig[self::MENU_CONFIG_KEY])) {
                    $this->mergeMenuConfig($entitiesConfig, $bundleConfig[self::MENU_CONFIG_KEY]);
                }

                // Merge titles from bundle configuration
                if (isset($bundleConfig[self::TITLES_KEY])) {
                    $titlesConfig += is_array($bundleConfig[self::TITLES_KEY])
                        ? $bundleConfig[self::TITLES_KEY]
                        : array();
                }
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
        $config = $this->processConfiguration($configuration, $entitiesConfig);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

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
            ->getDefinition('oro_navigation.title_service')
            ->addMethodCall('setTitles', array($titlesConfig));
    }

    /**
     * Merge menu configuration.
     *
     * @param array $config
     * @param array $configPart
     */
    protected function mergeMenuConfig(array &$config, array $configPart)
    {
        foreach ($configPart as $entity => $entityConfig) {
            if (isset($config['oro_menu_config'][$entity])) {
                $config[self::MENU_CONFIG_KEY][$entity] =
                    array_replace_recursive($config[self::MENU_CONFIG_KEY][$entity], $entityConfig);
            } else {
                $config[self::MENU_CONFIG_KEY][$entity] = $entityConfig;
            }
        }
    }
}
