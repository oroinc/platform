<?php

namespace Oro\Bundle\NavigationBundle\Configuration;

use Oro\Bundle\NavigationBundle\Configuration\Definition\MenuTreeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/navigation.yml" files.
 */
class NavigationConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'navigation';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE, 'array', new MenuTreeBuilder());
        $rootNode = $treeBuilder->getRootNode();

        $node = $rootNode->children();
        $this->appendMenuConfig($node);
        $this->appendNavigationElements($node);
        $this->appendTitles($node);

        return $treeBuilder;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function appendMenuConfig(NodeBuilder $node)
    {
        $node->arrayNode('menu_config')
            ->children()
                ->arrayNode('templates')
                    ->useAttributeAsKey('templates')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('template')->end()
                            ->scalarNode('clear_matcher')->end()
                            ->scalarNode('depth')->end()
                            ->scalarNode('allow_safe_labels')->end()
                            ->scalarNode('current_as_link')->end()
                            ->scalarNode('current_class')->end()
                            ->scalarNode('ancestor_class')->end()
                            ->scalarNode('first_class')->end()
                            ->scalarNode('last_class')->end()
                            ->scalarNode('compressed')->end()
                            ->scalarNode('block')->end()
                            ->scalarNode('root_class')->end()
                            ->scalarNode('is_dropdown')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('items')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('id')->end()
                            ->scalarNode('name')->end()
                            ->scalarNode('label')->end()
                            ->scalarNode('uri')->end()
                            ->scalarNode('route')->end()
                            ->arrayNode('route_parameters')
                                ->useAttributeAsKey('route_parameters')->prototype('scalar')->end()
                            ->end()
                            ->integerNode('position')->end()
                            ->booleanNode('read_only')->end()
                            ->scalarNode('acl_resource_id')->end()
                            ->scalarNode('translate_domain')->end()
                            ->arrayNode('translate_parameters')
                                ->useAttributeAsKey('translate_parameters')->prototype('scalar')->end()
                            ->end()
                            ->booleanNode('translate_disabled')->end()
                            ->arrayNode('attributes')
                                ->children()
                                    ->scalarNode('class')->end()
                                    ->scalarNode('id')->end()
                                ->end()
                            ->end()
                            ->arrayNode('linkAttributes')
                                ->children()
                                    ->scalarNode('class')->end()
                                    ->scalarNode('id')->end()
                                    ->scalarNode('target')->end()
                                    ->scalarNode('title')->end()
                                    ->scalarNode('rel')->end()
                                    ->scalarNode('type')->end()
                                    ->scalarNode('name')->end()
                                    ->scalarNode('type')->end()
                                ->end()
                            ->end()
                            ->arrayNode('children_attributes')
                                ->children()
                                    ->scalarNode('class')->end()
                                    ->scalarNode('id')->end()
                                ->end()
                            ->end()
                            ->arrayNode('label_attributes')
                                ->children()
                                    ->scalarNode('class')->end()
                                    ->scalarNode('id')->end()
                                    ->scalarNode('dir')->end()
                                ->end()
                            ->end()
                            ->scalarNode('display')->end()
                            ->scalarNode('display_children')->end()
                            ->scalarNode('type')->end()
                            ->arrayNode('extras')
                                ->useAttributeAsKey('extras')->prototype('variable')->end()
                            ->end()
                            ->booleanNode('show_non_authorized')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('tree')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')->end()
                            ->scalarNode('scope_type')->end()
                            ->scalarNode('read_only')->end()
                            ->scalarNode('max_nesting_level')->end()
                            ->arrayNode('extras')
                                ->useAttributeAsKey('extras')
                                ->prototype('scalar')->end()
                            ->end()
                            ->menuNode('children')->menuNodeHierarchy()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function appendNavigationElements(NodeBuilder $node)
    {
        $node->arrayNode('navigation_elements')
            ->useAttributeAsKey('id')
            ->prototype('array')
                ->children()
                    ->booleanNode('default')->defaultFalse()->end()
                    ->arrayNode('routes')
                        ->useAttributeAsKey('routes')
                        ->prototype('boolean')
                            ->treatNullLike(false)
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function appendTitles(NodeBuilder $node)
    {
        $node->arrayNode('titles')
            ->useAttributeAsKey('titles')
            ->prototype('scalar')
            ->end()
        ->end();
    }
}
