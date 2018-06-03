<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSearchExtension extends Extension
{
    const SEARCH_FILE_ROOT_NODE = 'search';

    /**
     * @param  array $configs
     * @param  ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // load entity search configuration from search.yml files
        $ymlLoader = new YamlCumulativeFileLoader('Resources/config/oro/search.yml');
        $configurationLoader = new CumulativeConfigLoader('oro_search', $ymlLoader);
        $engineResources = $configurationLoader->load($container);

        $entitiesConfigPart = [];
        foreach ($engineResources as $resource) {
            $entitiesConfigPart[] = $resource->data[self::SEARCH_FILE_ROOT_NODE];
        }

        // Process and merge configuration for entities_config section
        $processedEntitiesConfig = $this->processConfiguration(new EntitiesConfigConfiguration(), $entitiesConfigPart);

        $configs = $this->mergeConfigs($configs, $processedEntitiesConfig);

        // parse and validate configuration
        $config = $this->processConfiguration(new Configuration(), $configs);

        // set configuration parameters to container
        $container->setParameter('oro_search.engine', $config['engine']);
        $container->setParameter('oro_search.engine_parameters', $config['engine_parameters']);
        $container->setParameter('oro_search.log_queries', $config['log_queries']);
        $this->setEntitiesConfigParameter($container, $config[EntitiesConfigConfiguration::ROOT_NODE]);
        $container->setParameter('oro_search.twig.item_container_template', $config['item_container_template']);

        // load engine specific and general search services
        $serviceLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $serviceLoader->load('services.yml');
        $serviceLoader->load('filters.yml');
        $serviceLoader->load('commands.yml');

        $ymlLoader = new YamlCumulativeFileLoader('Resources/config/oro/search_engine/' . $config['engine'] . '.yml');
        $engineLoader = new CumulativeConfigLoader('oro_search', $ymlLoader);
        $engineResources = $engineLoader->load($container);

        foreach ($engineResources as $engineResource) {
            $serviceLoader->load($engineResource->path);
        }
    }

    /**
     * @param array $configs
     * @param array $processedEntitiesConfig
     * @return array
     */
    protected function mergeConfigs(array $configs, array $processedEntitiesConfig)
    {
        // replace configuration from bundles by configuration from mail config file
        if (isset($configs[Configuration::ROOT_NODE][EntitiesConfigConfiguration::ROOT_NODE])) {
            $configs[Configuration::ROOT_NODE][EntitiesConfigConfiguration::ROOT_NODE] = array_merge(
                $processedEntitiesConfig,
                $configs[Configuration::ROOT_NODE][EntitiesConfigConfiguration::ROOT_NODE]
            );
        } else {
            $configs[Configuration::ROOT_NODE][EntitiesConfigConfiguration::ROOT_NODE] = $processedEntitiesConfig;
        }

        return $configs;
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
     * @param ContainerBuilder $container
     * @param array $config
     * @deprecated since 1.9, will be removed after 1.11
     * Please use oro_search.provider.search_mapping service for mapping config
     */
    protected function setEntitiesConfigParameter(ContainerBuilder $container, array $config)
    {
        $container->setParameter('oro_search.entities_config', $config);
    }
}
