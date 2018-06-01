<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroIntegrationExtension extends Extension
{
    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('actions.yml');
        $loader->load('services.yml');
        $loader->load('rest_transport.yml');
        $loader->load('repositories.yml');
        $loader->load('factories.yml');
        $loader->load('action_handler.yml');
        $loader->load('commands.yml');
    }
}
