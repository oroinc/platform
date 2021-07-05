<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\NullCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSearchExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('oro_search.engine', $config['engine']);
        $container->setParameter('oro_search.required_plugins', $config['required_plugins']);
        $container->setParameter('oro_search.engine_parameters', $config['engine_parameters']);
        $container->setParameter('oro_search.log_queries', $config['log_queries']);
        $container->setParameter('oro_search.twig.item_container_template', $config['item_container_template']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('filters.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');

        $configLoader = new CumulativeConfigLoader(
            'oro_search',
            new NullCumulativeFileLoader('Resources/config/oro/search_engine/' . $config['engine'] . '.yml')
        );
        $resources = $configLoader->load(new ContainerBuilderAdapter($container));
        foreach ($resources as $resource) {
            $loader->load($resource->path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'oro_search';
    }
}
