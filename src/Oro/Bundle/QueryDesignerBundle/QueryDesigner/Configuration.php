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
                                        ->scalarNode('type')                // field type
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('entity')              // entity name
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('field')               // field name
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->booleanNode('identifier')         // primary key
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
                                        ->scalarNode('type')                // field type
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('entity')              // entity name
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('field')               // field name
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->booleanNode('identifier')         // primary key
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('function')
                                ->prototype('scalar')
                                    ->cannotBeEmpty()
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
