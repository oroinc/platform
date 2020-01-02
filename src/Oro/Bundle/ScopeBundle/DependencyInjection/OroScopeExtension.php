<?php

namespace Oro\Bundle\ScopeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroScopeExtension extends Extension
{
    const ALIAS = 'oro_scope';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
            // use a memory to cache scopes to avoid influence functional tests to each other
            $container->removeDefinition('oro_scope.scope_cache');
            $container->register('oro_scope.scope_cache', 'Doctrine\Common\Cache\ArrayCache')
                ->setPublic(false);
        }
    }
}
