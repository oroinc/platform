<?php

namespace Oro\Bundle\SidebarBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for sidebar widget definitions
 * that is loaded from "Resources/public/sidebar_widgets/{folder}/widget.yml" files.
 */
class WidgetDefinitionConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sidebar_widgets');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
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
                    ->scalarNode('dialogIcon')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('module')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('cssClass')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('description')
                        ->info('translatable description')
                    ->end()
                    ->scalarNode('placement')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->booleanNode('showRefreshButton')
                        ->defaultTrue()
                    ->end()
                    ->variableNode('settings')
                        ->defaultNull()
                    ->end()
                    ->booleanNode('isNew')
                        ->defaultFalse()
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
            ->end();

        return $treeBuilder;
    }
}
