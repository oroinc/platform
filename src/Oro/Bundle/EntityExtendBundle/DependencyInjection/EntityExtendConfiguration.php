<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from entity_extend.yml
 */
class EntityExtendConfiguration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_entity_extend';
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root(self::ROOT_NODE)
            ->children()
                ->arrayNode('underlying_types')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
