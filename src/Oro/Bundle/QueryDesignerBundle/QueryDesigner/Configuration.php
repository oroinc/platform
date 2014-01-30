<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

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
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root(self::ROOT_NODE_NAME)
            ->children()
                ->arrayNode('filters')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->ignoreExtraKeys()
                        ->children()
                            ->arrayNode('applicable')
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        // field type
                                        ->scalarNode('type')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // entity name
                                        ->scalarNode('entity')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // field name
                                        ->scalarNode('field')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // primary key
                                        ->booleanNode('identifier')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('type')
                                ->isRequired()
                                ->validate()
                                ->ifNotInArray($this->types)
                                    ->thenInvalid('Invalid filter type "%s"')
                                ->end()
                            ->end()
                            ->scalarNode('template_theme')
                                ->defaultValue('embedded')
                            ->end()
                            ->arrayNode('query_type')
                                ->isRequired()
                                ->requiresAtLeastOneElement()
                                ->prototype('scalar')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('grouping')
                    ->ignoreExtraKeys()
                    ->children()
                        ->arrayNode('exclude')
                            ->prototype('array')
                                ->children()
                                    // field type
                                    ->scalarNode('type')
                                        ->cannotBeEmpty()
                                    ->end()
                                    // entity name
                                    ->scalarNode('entity')
                                        ->cannotBeEmpty()
                                    ->end()
                                    // field name
                                    ->scalarNode('field')
                                        ->cannotBeEmpty()
                                    ->end()
                                    // primary key
                                    ->booleanNode('identifier')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('converters')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->ignoreExtraKeys()
                        ->children()
                            ->arrayNode('applicable')
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        // field type
                                        ->scalarNode('type')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // parent entity name
                                        ->scalarNode('parent_entity')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // entity name
                                        ->scalarNode('entity')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // field name
                                        ->scalarNode('field')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // primary key
                                        ->booleanNode('identifier')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('functions')
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        // function name
                                        ->scalarNode('name')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        // function return type
                                        // if this attribute is not specified the return type
                                        // is equal to the type of a field this function is applied
                                        ->scalarNode('return_type')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // function expression
                                        ->scalarNode('expr')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        // label name for function name
                                        // usually this attribute sets automatically (see ConfigurationPass class) to
                                        // [vendor name].query_designer.converters.[converter name].[function name].name
                                        // the vendor name is always in lower case
                                        // if your function overrides existing function (the name of your function
                                        // is the same as the name of existing function) and you want to use a label
                                        // of the overridden function set this attribute to true (boolean)
                                        ->scalarNode('name_label')
                                            ->isRequired()
                                        ->end()
                                        // label name for function hint
                                        // usually this attribute sets automatically (see ConfigurationPass class) to
                                        // [vendor name].query_designer.converters.[converter name].[function name].hint
                                        // the vendor name is always in lower case
                                        // if your function overrides existing function (the name of your function
                                        // is the same as the name of existing function) and you want to use a label
                                        // of the overridden function set this attribute to true (boolean)
                                        ->scalarNode('hint_label')
                                            ->isRequired()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('query_type')
                                ->isRequired()
                                ->requiresAtLeastOneElement()
                                ->prototype('scalar')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('aggregates')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->ignoreExtraKeys()
                        ->children()
                            ->arrayNode('applicable')
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        // field type
                                        ->scalarNode('type')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // parent entity name
                                        ->scalarNode('parent_entity')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // entity name
                                        ->scalarNode('entity')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // field name
                                        ->scalarNode('field')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // primary key
                                        ->booleanNode('identifier')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('functions')
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        // function name
                                        ->scalarNode('name')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        // function return type
                                        // if this attribute is not specified the return type
                                        // is equal to the type of a field this function is applied
                                        ->scalarNode('return_type')
                                            ->cannotBeEmpty()
                                        ->end()
                                        // function expression
                                        ->scalarNode('expr')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        // label name for function name
                                        // usually this attribute sets automatically (see ConfigurationPass class) to
                                        // [vendor name].query_designer.aggregates.[converter name].[function name].name
                                        // the vendor name is always in lower case
                                        // if your function overrides existing function (the name of your function
                                        // is the same as the name of existing function) and you want to use a label
                                        // of the overridden function set this attribute to true (boolean)
                                        ->scalarNode('name_label')
                                            ->isRequired()
                                        ->end()
                                        // label name for function hint
                                        // usually this attribute sets automatically (see ConfigurationPass class) to
                                        // [vendor name].query_designer.aggregates.[converter name].[function name].hint
                                        // the vendor name is always in lower case
                                        // if your function overrides existing function (the name of your function
                                        // is the same as the name of existing function) and you want to use a label
                                        // of the overridden function set this attribute to true (boolean)
                                        ->scalarNode('hint_label')
                                            ->isRequired()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('query_type')
                                ->isRequired()
                                ->requiresAtLeastOneElement()
                                ->prototype('scalar')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
