<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all datagrid configuration providers, data sources and extensions.
 */
class ConfigurationPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;
    use TaggedServiceTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerConfigurationProviders($container);
        $this->registerDataSources($container);
        $this->registerExtensions($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerConfigurationProviders(ContainerBuilder $container): void
    {
        $this->registerTaggedServicesViaAddMethod(
            $container,
            'oro_datagrid.configuration.provider.chain',
            'addProvider',
            $this->findAndSortTaggedServices('oro_datagrid.configuration.provider', $container)
        );
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerDataSources(ContainerBuilder $container): void
    {
        $builderDef = $container->getDefinition('oro_datagrid.datagrid.builder');
        $taggedServices = $container->findTaggedServiceIds('oro_datagrid.datasource');
        foreach ($taggedServices as $id => $tags) {
            $type = $this->getRequiredAttribute($tags[0], 'type', $id, 'oro_datagrid.datasource');
            $builderDef->addMethodCall('registerDatasource', [$type, new Reference($id)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerExtensions(ContainerBuilder $container): void
    {
        $this->registerTaggedServicesViaAddMethod(
            $container,
            'oro_datagrid.datagrid.builder',
            'registerExtension',
            $this->findAndSortTaggedServices('oro_datagrid.extension', $container)
        );
    }
}
