<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroWorkflowExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('processors.yml');
        $loader->load('prototypes.yml');
        $loader->load('actions.yml');
        $loader->load('conditions.yml');
        $loader->load('assemblers.yml');
        $loader->load('field.yml');
        $loader->load('form_types.yml');
        $loader->load('serializer.yml');
        $loader->load('configuration.yml');
        $loader->load('twig_extensions.yml');
        $loader->load('listener.yml');
        $loader->load('validator.yml');
        $loader->load('security.yml');
        $loader->load('grid.yml');
        $loader->load('cache.yml');
        $loader->load('client.yml');
        $loader->load('commands.yml');

        if ($container->getParameter('kernel.environment') === 'test') {
            $loader->load('services_test.yml');
        }
    }
}
