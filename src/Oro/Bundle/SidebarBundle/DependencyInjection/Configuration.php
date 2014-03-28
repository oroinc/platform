<?php

namespace Oro\Bundle\SidebarBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

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
                            ->scalarNode('cssClass')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('placement')
                                ->cannotBeEmpty()
                            ->end()
                            ->variableNode('settings')
                                ->defaultValue(null)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'sidebar_left_active'  => ['value' => false, 'type' => 'bool'],
                'sidebar_right_active' => ['value' => true, 'type' => 'bool']
            ]
        );

        return $treeBuilder;
    }
}
