<?php

namespace Oro\Bundle\ScopeBundle\DependencyInjection;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroScopeExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
            // use a memory to cache scopes to avoid influence functional tests to each other
            $symfonyAdapterDefinition = new Definition(ArrayAdapter::class, [0, false]);
            $container->setDefinition('oro_scope.scope_cache', $symfonyAdapterDefinition);
        }
    }
}
