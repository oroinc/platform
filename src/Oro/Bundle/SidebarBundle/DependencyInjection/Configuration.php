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
                                ->defaultNull()
                            ->end()
                            ->scalarNode('iconClass')
                                ->defaultNull()
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
                            ->booleanNode('showRefreshButton')
                                ->defaultTrue()
                            ->end()
                            ->variableNode('settings')
                                ->defaultNull()
                            ->end()
                        ->end()
                        ->validate()
                            ->ifTrue(function ($value) {
                                return (empty($value['icon']) && empty($value['iconClass']))
                                    || (!empty($value['icon']) && !empty($value['iconClass']));
                            })
                            ->thenInvalid('Either icon or iconClass option is required for sidebar widget')
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
