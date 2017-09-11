<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_entity');

        $rootNode->children()
            ->integerNode('default_query_cache_lifetime')
                ->info('Default doctrine`s query cache lifetime')
                ->defaultNull()
                ->min(1)
            ->end()
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
                                            ->booleanNode('translatable')->defaultTrue()->end()
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
            ->arrayNode('entity_aliases')
                ->info('Entity aliases')
                ->example(
                    [
                        'Acme\Bundle\Entity\SomeEntity' => [
                            'alias' => 'someentity',
                            'plural_alias' => 'someentities'
                        ]
                    ]
                )
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('alias')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(
                                    function ($v) {
                                        return !preg_match('/^[a-z][a-z0-9_]*$/D', $v);
                                    }
                                )
                                ->thenInvalid(
                                    'The value %s cannot be used as an entity alias '
                                    . 'because it contains illegal characters. '
                                    . 'The valid alias should start with a letter and only contain '
                                    . 'lower case letters, numbers and underscores ("_").'
                                )
                            ->end()
                        ->end()
                        ->scalarNode('plural_alias')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(
                                    function ($v) {
                                        return !preg_match('/^[a-z][a-z0-9_]*$/D', $v);
                                    }
                                )
                                ->thenInvalid(
                                    'The value %s cannot be used as an entity plural alias '
                                    . 'because it contains illegal characters. '
                                    . 'The valid alias should start with a letter and only contain '
                                    . 'lower case letters, numbers and underscores ("_").'
                                )
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('entity_alias_exclusions')
                ->info('Entities which has no aliases')
                ->example(
                    [
                        'Acme\Bundle\Entity\SomeEntity1',
                        'Acme\Bundle\Entity\SomeEntity2'
                    ]
                )
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('entity_name_formats')
                ->info('Formats of entity text representation')
                ->example(
                    [
                        'long' => [
                            'fallback' => 'short'
                        ],
                        'short' => null,
                        'html' => [
                            'fallback'  => 'long',
                            'decorator' => true
                        ]
                    ]
                )
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('fallback')->defaultValue(null)->end()
                    ->end()
                ->end()
                ->validate()
                    ->always(
                        function ($v) {
                            $known        = array_fill_keys(array_keys($v), true);
                            $dependencies = [];
                            foreach ($v as $name => $item) {
                                if (empty($item['fallback'])) {
                                    continue;
                                }
                                $fallback = $item['fallback'];
                                if (!isset($known[$fallback])) {
                                    throw new InvalidConfigurationException(
                                        sprintf(
                                            'The undefined text representation format "%s" cannot be used as '
                                            . 'a fallback format for the format "%s".',
                                            $fallback,
                                            $name
                                        )
                                    );
                                }
                                if ($name === $fallback) {
                                    throw new InvalidConfigurationException(
                                        sprintf(
                                            'The text representation format "%s" have '
                                            . 'a cyclic dependency on itself.',
                                            $name
                                        )
                                    );
                                }
                                $dependencies[$name] = [$fallback];
                            }
                            $continue = true;
                            while ($continue) {
                                $continue = false;
                                foreach ($v as $name => $item) {
                                    if (empty($item['fallback'])) {
                                        continue;
                                    }
                                    $fallback = $item['fallback'];
                                    foreach ($dependencies as $depName => &$depItems) {
                                        if ($depName === $name) {
                                            continue;
                                        }
                                        if (in_array($name, $depItems, true)) {
                                            if (in_array($fallback, $depItems, true)) {
                                                continue;
                                            }
                                            $depItems[] = $fallback;
                                            if ($fallback === $depName) {
                                                throw new InvalidConfigurationException(
                                                    sprintf(
                                                        'The text representation format "%s" have '
                                                        . 'a cyclic dependency "%s".',
                                                        $depName,
                                                        implode(' -> ', $depItems)
                                                    )
                                                );
                                            }
                                            $continue   = true;
                                        }
                                    }
                                }
                            }

                            return $v;
                        }
                    )
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
