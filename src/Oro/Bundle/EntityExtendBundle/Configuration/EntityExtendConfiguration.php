<?php

namespace Oro\Bundle\EntityExtendBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/entity_extend.yml" files.
 */
class EntityExtendConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'entity_extend';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $treeBuilder->getRootNode()
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
