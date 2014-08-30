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
    /**
     * @param  array            $configs
     * @param  ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // load entity search configuration from search.yml files
        $configPart          = array();
        $ymlLoader           = new YamlCumulativeFileLoader('Resources/config/search.yml');
        $configurationLoader = new CumulativeConfigLoader('oro_search', $ymlLoader);
        $engineResources     = $configurationLoader->load($container);

        foreach ($engineResources as $resource) {
            $configPart += $resource->data;
        }

        // merge entity configuration with main configuration
        if (isset($configs[0]['entities_config'])) {
            $configs[0]['entities_config'] = array_merge($configPart, $configs[0]['entities_config']);
        } else {
            $configs[0]['entities_config'] = $configPart;
        }

        // parse and validate configuration
        $config = $this->processConfiguration(new Configuration(), $configs);

        // set configuration parameters to container
        $container->setParameter('oro_search.engine', $config['engine']);
        $container->setParameter('oro_search.engine_parameters', $config['engine_parameters']);
        $container->setParameter('oro_search.log_queries', $config['log_queries']);
        $container->setParameter('oro_search.realtime_update', $config['realtime_update']);
        $container->setParameter('oro_search.entities_config', $config['entities_config']);
        $container->setParameter('oro_search.twig.item_container_template', $config['item_container_template']);

        // load engine specific and general search services
        $serviceLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $serviceLoader->load('services.yml');

        $ymlLoader = new YamlCumulativeFileLoader('Resources/config/oro/search_engine/' . $config['engine'] . '.yml');
        $engineLoader = new CumulativeConfigLoader('oro_search', $ymlLoader);
        $engineResources = $engineLoader->load($container);

        if (!empty($engineResources)) {
            $resource = end($engineResources);
            $serviceLoader->load($resource->path);
        }
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
}
