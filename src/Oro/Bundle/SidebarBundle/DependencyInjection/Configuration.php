<?php

namespace Oro\Bundle\SidebarBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_sidebar');

        $rootNode
            ->children()
                ->arrayNode('sidebar_widgets')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('title')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('icon')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('module')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('placement')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
