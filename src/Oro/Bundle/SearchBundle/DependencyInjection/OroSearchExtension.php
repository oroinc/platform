<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSearchExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('oro_search.engine_dsn', $config['engine_dsn']);
        $container->setParameter('oro_search.required_plugins', $config['required_plugins']);
        $container->setParameter('oro_search.required_attributes', $config['required_attributes']);
        $container->setParameter('oro_search.engine_parameters', $config['engine_parameters']);
        $container->setParameter('oro_search.log_queries', $config['log_queries']);
        $container->setParameter('oro_search.twig.item_container_template', $config['item_container_template']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('filters.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');
        $loader->load('search.yml');
        $loader->load('mq_topics.yml');
    }
}
