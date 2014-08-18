<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroSearchExtension extends Extension
{
    /**
     * Load configuration
     *
     * @param  array            $configs
     * @param  ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $alias         = $this->getAlias();
        $searchConfigs = array();
        $configuration = new Configuration();
        $configLoader  = new CumulativeConfigLoader(
            $alias,
            new YamlCumulativeFileLoader('Resources/config/search_definition.yml')
        );
        $resources     = $configLoader->load($container);

        foreach ($resources as $resource) {
            $searchConfigs[] = $resource->data[$alias];
        }

        foreach ($configs as $config) {
            $searchConfigs[] = $config;
        }

        $config     = $this->processConfiguration($configuration, $searchConfigs);
        $loader     = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $engineFile = 'engine/' . $config['engine'] . '.yml';

        $container->setParameter($alias . '.log_queries', $config['log_queries']);

        $this->searchMappingsConfig($config, $container);
        $loader->load($engineFile);

        if ($config['engine'] == 'orm') {
            $driversAlias = $alias . '.drivers';

            if ($container->hasParameter($driversAlias)) {
                $config['drivers'] = $container->getParameter($driversAlias);
            } else {
                throw new InvalidConfigurationException(sprintf(
                    '"%s" are required in the "%s" file for ORM engine',
                    $driversAlias,
                    $engineFile
                ));
            }

            $this->remapParameters($config, $container, array('drivers' => $driversAlias));
        }

        $container->setParameter($alias . '.realtime_update', $config['realtime_update']);
        $loader->load('services.yml');
        $container->setParameter($alias . '.twig.item_container_template', $config['item_container_template']);
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'oro_search';
    }

    /**
     * Add search mapping config
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function searchMappingsConfig(array $config, ContainerBuilder $container)
    {
        $alias          = $this->getAlias();
        $entitiesConfig = $config['entities_config'];
        $configLoader   = new CumulativeConfigLoader(
            $alias,
            new YamlCumulativeFileLoader('Resources/config/search.yml')
        );
        $resources      = $configLoader->load($container);

        foreach ($resources as $resource) {
            $entitiesConfig[] = $resource->data;
        }

        $container->setParameter($alias . '.entities_config', $entitiesConfig);
    }

    /**
     * Remap parameters form to container params
     *
     * @param array            $config
     * @param ContainerBuilder $container
     * @param array            $map
     */
    protected function remapParameters(array $config, ContainerBuilder $container, array $map)
    {
        foreach ($map as $name => $paramName) {
            if (array_key_exists($name, $config)) {
                $container->setParameter($paramName, $config[$name]);
            }
        }
    }
}
