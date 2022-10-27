<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all extensions for datagrids.
 */
class ExtensionsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_datagrid.datagrid.builder')
            ->setArgument(
                '$extensions',
                new IteratorArgument($this->findAndSortTaggedServices('oro_datagrid.extension', $container))
            );
    }
}
