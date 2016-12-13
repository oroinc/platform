<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\NavigationBundle\Config\Definition\Builder\MenuTreeBuilder;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_menu_config';
    const NAVIGATION_ELEMENTS_NODE = 'oro_navigation_elements';


    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::ROOT_NODE, 'array', new MenuTreeBuilder());

        $node = $rootNode->children();
        $this->setChildren($node);
        $node->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'max_items'       => ['value' => 20],
                'title_suffix'    => ['value' => ''],
                'title_delimiter' => ['value' => '-'],
                'breadcrumb_menu' => ['value' => 'application_menu'],
            ]
        );

        return $treeBuilder;
    }

    /**
     * Add children nodes to menu
     *
     * @param $node NodeBuilder
     * @return Configuration
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setChildren($node)
    {
        $node->
            arrayNode(self::NAVIGATION_ELEMENTS_NODE)
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
        ->end()
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
                    ->scalarNode('read_only')->end()
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
                        ->scalarNode('area')->end()
                        ->scalarNode('read_only')->end()
                        ->scalarNode('max_nesting_level')->end()
                        ->arrayNode('extras')
                            ->useAttributeAsKey('extras')->prototype('scalar')->end()
                        ->end()
                        ->menuNode('children')->menuNodeHierarchy()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $this;
    }
}
