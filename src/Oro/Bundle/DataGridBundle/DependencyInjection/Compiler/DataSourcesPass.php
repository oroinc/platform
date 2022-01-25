<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all data sources for datagrids.
 */
class DataSourcesPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_datagrid.datagrid.builder')
            ->setArgument(
                '$dataSources',
                ServiceLocatorTagPass::register(
                    $container,
                    $this->findAndSortTaggedServices('oro_datagrid.datasource', 'type', $container)
                )
            );
    }
}
