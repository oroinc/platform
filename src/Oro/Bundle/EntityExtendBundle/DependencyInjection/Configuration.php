<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_entity_extend');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('backup')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.project_dir%/var/backup')
                ->end()
                ->arrayNode('custom_entities')
                    ->scalarPrototype()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
