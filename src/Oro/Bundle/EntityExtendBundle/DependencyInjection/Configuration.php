<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_entity_extend');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('backup')->cannotBeEmpty()->defaultValue('%kernel.project_dir%/var/backup')->end()
            ->end();

        return $treeBuilder;
    }
}
