<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSecurityExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->prependExtensionConfig(
            $this->getAlias(),
            array_intersect_key(
                $this->processConfiguration(new Configuration(), $configs),
                array_flip(['settings'])
            )
        );

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('layouts.yml');
        $loader->load('ownership.yml');
        $loader->load('services.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }

        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $loader->load('services_debug.yml');
        }
    }
}
