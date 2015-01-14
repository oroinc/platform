<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
            ->arrayNode('virtual_relations')
                ->info('Entity virtual relations definitions')
                ->example(
                    [
                        'Acme\Bundle\Entity\SomeEntity' => [
                            'virtual_relation1' => [
                                'relation_type' => 'manyToOne',
                                'related_entity_name' => 'Acme\Bundle\Entity\GroupEntity',
                                // required if you need to join on specific join alias
                                'target_join_alias' => 'group_entity',
                                'label' => 'Group',
                                'query' => [
                                    'join' => [
                                        'left' => [
                                            [
                                                'join' => 'Acme\Bundle\Entity\GroupEntity',
                                                'alias' => 'group_entity',
                                                'conditionType' => 'WITH',
                                                'condition' => 'group_entity.code = entity.group_code'
                                            ]
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
                            ->scalarNode('relation_type')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('related_entity_name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('target_join_alias')
                            ->end()
                            ->scalarNode('label')
                            ->end()
                            ->arrayNode('query')
                                ->children()
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
