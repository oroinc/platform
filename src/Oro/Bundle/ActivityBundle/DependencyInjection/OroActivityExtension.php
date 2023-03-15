<?php

namespace Oro\Bundle\ActivityBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroActivityExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('form.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');

        $container->getDefinition('oro_activity.api.activity_association_provider')
            ->setArgument('$activityAssociationNames', $config['api']['activity_association_names']);
    }
}
