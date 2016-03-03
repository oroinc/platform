<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroActionExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('cache.yml');
        $loader->load('conditions.yml');
        $loader->load('configuration.yml');
        $loader->load('form_types.yml');
        $loader->load('functions.yml');
        $loader->load('services.yml');
        $loader->load('twig_extensions.yml');
    }
}
