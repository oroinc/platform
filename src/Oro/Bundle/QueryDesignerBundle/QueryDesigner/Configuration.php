<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE_NAME = 'query_designer';

    /** @var array */
    protected $types;

    /**
     * @param $types
     */
    public function __construct($types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root(self::ROOT_NODE_NAME)
            ->children()
                ->append($this->getFiltersConfigTree())
                ->append($this->getGroupingConfigTree())
                ->append($this->getConvertersConfigTree())
                ->append($this->getAggregatorsConfigTree())
            ->end();

        return $builder;
    }

    /**
     * Filters configuration tree
     *
     * @return NodeDefinition
     */
    protected function getFiltersConfigTree()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('filters');

        $node->useAttributeAsKey('name')
            ->prototype('array')
                ->ignoreExtraKeys()
                ->children()
                    // defines criteria a field should satisfy in order to use a filter
                    ->arrayNode('applicable')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->children()
                                // field data type
                                ->scalarNode('type')->cannotBeEmpty()->end()
                                // entity name
                                ->scalarNode('entity')->cannotBeEmpty()->end()
                                // field name
                                ->scalarNode('field')->cannotBeEmpty()->end()
                                // indicates whether a field should be a primary key or not
                                ->booleanNode('identifier')->end()
                            ->end()
                        ->end()
                    ->end()
                    // filter type
                    ->scalarNode('type')
                        ->isRequired()
                        ->validate()
                        ->ifNotInArray($this->types)
                            ->thenInvalid('Invalid filter type "%s"')
                        ->end()
                    ->end()
                    // filter options
                    ->arrayNode('options')
                        ->useAttributeAsKey('name')
                        ->prototype('variable')->end()
                    ->end()
                    // a theme type should be used to render a filter
                    ->scalarNode('template_theme')
                        ->defaultValue('embedded')
                    ->end()
                    // JS module should be used to initialize a filter. This module should returns the following
                    // function "function (filterOptions, context)" and should return promise (jQuery.Deferred instance)
                    // see _createFilter() method in 'field-condition.js' file
                    ->scalarNode('init_module')->end()
                    // types of queries a filter is available. use "all" if it should be available in any query
                    ->arrayNode('query_type')
                        ->isRequired()
                        ->requiresAtLeastOneElement()
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                    ->scalarNode('placeholder')->end()
                ->end()
                ->validate()
                    ->always(
                        function ($value) {
                            if (empty($value['options'])) {
                                unset($value['options']);
                            }
                            if (empty($value['init_module'])) {
                                unset($value['init_module']);
                            }
                            return $value;
                        }
                    )
                ->end()
            ->end();

        return $node;
    }

    /**
     * Grouping configuration tree
     *
     * @return NodeDefinition
     */
    protected function getGroupingConfigTree()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('grouping');

        $node->ignoreExtraKeys()
            ->children()
                ->arrayNode('exclude')
                    ->prototype('array')
                        ->children()
                            // field data type
                            ->scalarNode('type')->cannotBeEmpty()->end()
                            // entity name
                            ->scalarNode('entity')->cannotBeEmpty()->end()
                            // field name
                            ->scalarNode('field')->cannotBeEmpty()->end()
                            // indicates whether a field should be a primary key or not
                            ->booleanNode('identifier')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }

    /**
     * Converters configuration tree
     *
     * @return NodeDefinition
     */
    protected function getConvertersConfigTree()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('converters');

        $node->useAttributeAsKey('name')
            ->prototype('array')
                ->ignoreExtraKeys()
                ->children()
                    // defines criteria a field should satisfy in order to use a converter
                    ->arrayNode('applicable')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->children()
                                // field data type
                                ->scalarNode('type')->cannotBeEmpty()->end()
                                // parent entity name
                                ->scalarNode('parent_entity')->cannotBeEmpty()->end()
                                // entity name
                                ->scalarNode('entity')->cannotBeEmpty()->end()
                                // field name
                                ->scalarNode('field')->cannotBeEmpty()->end()
                                // indicates whether a field should be a primary key or not
                                ->booleanNode('identifier')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('functions')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->children()
                                // function name
                                ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                // function return type
                                // if this attribute is not specified the return type
                                // is equal to the type of a field this function is applied
                                ->scalarNode('return_type')->cannotBeEmpty()->end()
                                // function expression
                                ->scalarNode('expr')->isRequired()->cannotBeEmpty()->end()
                                // label name for function name
                                // usually this attribute sets automatically (see ConfigurationPass class) to
                                // [vendor name].query_designer.converters.[converter name].[function name].name
                                // the vendor name is always in lower case
                                // if your function overrides existing function (the name of your function
                                // is the same as the name of existing function) and you want to use a label
                                // of the overridden function set this attribute to true (boolean)
                                ->scalarNode('name_label')->isRequired()->end()
                                // label name for function hint
                                // usually this attribute sets automatically (see ConfigurationPass class) to
                                // [vendor name].query_designer.converters.[converter name].[function name].hint
                                // the vendor name is always in lower case
                                // if your function overrides existing function (the name of your function
                                // is the same as the name of existing function) and you want to use a label
                                // of the overridden function set this attribute to true (boolean)
                                ->scalarNode('hint_label')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                    // types of queries a converter is available. use "all" if it should be available in any query
                    ->arrayNode('query_type')
                        ->isRequired()
                        ->requiresAtLeastOneElement()
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * Aggregators configuration tree
     *
     * @return NodeDefinition
     */
    protected function getAggregatorsConfigTree()
    {
        $builder = new TreeBuilder();
        $node    = $builder->root('aggregates');

        $node->useAttributeAsKey('name')
            ->prototype('array')
                ->ignoreExtraKeys()
                ->children()
                    // defines criteria a field should satisfy in order to use an aggregate
                    ->arrayNode('applicable')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->children()
                                // field data type
                                ->scalarNode('type')->cannotBeEmpty()->end()
                                // parent entity name
                                ->scalarNode('parent_entity')->cannotBeEmpty()->end()
                                // entity name
                                ->scalarNode('entity')->cannotBeEmpty()->end()
                                // field name
                                ->scalarNode('field')->cannotBeEmpty()->end()
                                // indicates whether a field should be a primary key or not
                                ->booleanNode('identifier')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('functions')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->children()
                                // function name
                                ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                                // function return type
                                // if this attribute is not specified the return type
                                // is equal to the type of a field this function is applied
                                ->scalarNode('return_type')->cannotBeEmpty()->end()
                                // function expression
                                ->scalarNode('expr')->isRequired()->cannotBeEmpty()->end()
                                // label name for function name
                                // usually this attribute sets automatically (see ConfigurationPass class) to
                                // [vendor name].query_designer.aggregates.[converter name].[function name].name
                                // the vendor name is always in lower case
                                // if your function overrides existing function (the name of your function
                                // is the same as the name of existing function) and you want to use a label
                                // of the overridden function set this attribute to true (boolean)
                                ->scalarNode('name_label')->isRequired()->end()
                                // label name for function hint
                                // usually this attribute sets automatically (see ConfigurationPass class) to
                                // [vendor name].query_designer.aggregates.[converter name].[function name].hint
                                // the vendor name is always in lower case
                                // if your function overrides existing function (the name of your function
                                // is the same as the name of existing function) and you want to use a label
                                // of the overridden function set this attribute to true (boolean)
                                ->scalarNode('hint_label')->isRequired()->end()
                            ->end()
                        ->end()
                    ->end()
                    // types of queries a aggregate is available. use "all" if it should be available in any query
                    ->arrayNode('query_type')
                        ->isRequired()
                        ->requiresAtLeastOneElement()
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }
}
