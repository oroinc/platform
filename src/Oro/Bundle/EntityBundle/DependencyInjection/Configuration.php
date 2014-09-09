<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('oro_entity');

        $rootNode->children()
            ->arrayNode('exclusions')
                ->info('The list of entities and its fields to be excluded')
                ->example([['entity' => 'Acme\Bundle\Entity\SomeEntity', 'field' => 'some_field']])
                ->prototype('array')
                    ->children()
                        ->scalarNode('entity')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('field')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('virtual_fields')
                ->info('Entity virtual fields definitions')
                ->example(
                    [
                        'Acme\Bundle\Entity\SomeEntity' => [
                            'virtual_field1' => [
                                'query' => [
                                    'select' => [
                                        'expr' => 'COALESCE(entity.regionText, region.name)',
                                        'return_type' => 'string'
                                    ],
                                    'join' => [
                                        'left' => [
                                            ['join' => 'entity.region', 'alias' => 'region']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                )
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('query')
                                ->children()
                                    ->arrayNode('select')
                                        ->children()
                                            ->scalarNode('expr')
                                                ->isRequired()
                                                ->cannotBeEmpty()
                                            ->end()
                                            ->scalarNode('return_type')->end()
                                            ->scalarNode('label')->end()
                                            // set to true if original field name should be used in WHERE expression
                                            // rather that virtual field select expression.
                                            // it can be helpful if your virtual field overrides many-to-one or
                                            // or many-to-many relation and you want to filter records
                                            // by foreign key id (take in account that custom datagrid filter
                                            // should be created as well)
                                            ->booleanNode('filter_by_id')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('join')
                                        ->prototype('array')
                                            ->prototype('variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
