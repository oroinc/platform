<?php

namespace Oro\Bundle\NavigationBundle\Configuration\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Configuration definition for menu nodes.
 */
class MenuNodeDefinition extends ArrayNodeDefinition
{
    /**
     * Makes menu hierarchy.
     *
     * @param int $depth
     *
     * @return MenuNodeDefinition
     */
    public function menuNodeHierarchy(int $depth = 10)
    {
        if (0 === $depth) {
            return $this;
        }

        return $this->useAttributeAsKey('id')
            ->prototype('array')
                ->children()
                    ->scalarNode('position')->end()
                    ->scalarNode('merge_strategy')
                        ->defaultValue('move')
                    ->end()
                    ->menuNode('children')->menuNodeHierarchy($depth - 1)
                ->end()
            ->end();
    }
}
