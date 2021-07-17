<?php

namespace Oro\Bundle\EntityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/entity.yml" files.
 */
class EntityConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_entity';

    public const EXCLUSIONS              = 'exclusions';
    public const ENTITY_ALIASES          = 'entity_aliases';
    public const ENTITY_ALIAS_EXCLUSIONS = 'entity_alias_exclusions';
    public const VIRTUAL_FIELDS          = 'virtual_fields';
    public const VIRTUAL_RELATIONS       = 'virtual_relations';
    public const ENTITY_NAME_FORMATS     = 'entity_name_formats';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $node = $rootNode->children();
        $this->appendExclusions($node);
        $this->appendEntityAliases($node);
        $this->appendEntityAliasExclusions($node);
        $this->appendVirtualFields($node);
        $this->appendVirtualRelations($node);
        $this->appendEntityNameFormats($node);

        return $treeBuilder;
    }

    private function appendExclusions(NodeBuilder $builder)
    {
        $children = $builder
            ->arrayNode(self::EXCLUSIONS)
                ->info('The list of entities and its fields to be excluded')
                ->example([['entity' => 'Acme\Bundle\Entity\SomeEntity', 'field' => 'some_field']])
                ->prototype('array')
                    ->children();

        $children
            ->scalarNode('entity')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('field')->end();
    }

    private function appendEntityAliases(NodeBuilder $builder)
    {
        $children = $builder
            ->arrayNode(self::ENTITY_ALIASES)
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
                    ->children();

        $children
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
            ->end();
    }

    private function appendEntityAliasExclusions(NodeBuilder $builder)
    {
        $builder
            ->arrayNode(self::ENTITY_ALIAS_EXCLUSIONS)
                ->info('Entities which has no aliases')
                ->example(
                    [
                        'Acme\Bundle\Entity\SomeEntity1',
                        'Acme\Bundle\Entity\SomeEntity2'
                    ]
                )
                ->prototype('scalar')->end()
            ->end();
    }

    private function appendVirtualFields(NodeBuilder $builder)
    {
        $children = $builder
            ->arrayNode(self::VIRTUAL_FIELDS)
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
                        ->children();

        $children
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
            ->end();
    }

    private function appendVirtualRelations(NodeBuilder $builder)
    {
        $children = $builder
            ->arrayNode(self::VIRTUAL_RELATIONS)
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
                        ->children();

        $children
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
            ->end();
    }

    private function appendEntityNameFormats(NodeBuilder $builder)
    {
        $builder
            ->arrayNode(self::ENTITY_NAME_FORMATS)
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
                            $this->validateEntityNameFormats($v);

                            return $v;
                        }
                    )
                ->end()
            ->end();
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validateEntityNameFormats(array $v)
    {
        $known = array_fill_keys(array_keys($v), true);
        $dependencies = [];
        foreach ($v as $name => $item) {
            if (empty($item['fallback'])) {
                continue;
            }
            $fallback = $item['fallback'];
            if (!isset($known[$fallback])) {
                throw new InvalidConfigurationException(sprintf(
                    'The undefined text representation format "%s" cannot be used as '
                    . 'a fallback format for the format "%s".',
                    $fallback,
                    $name
                ));
            }
            if ($name === $fallback) {
                throw new InvalidConfigurationException(sprintf(
                    'The text representation format "%s" have '
                    . 'a cyclic dependency on itself.',
                    $name
                ));
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
                            throw new InvalidConfigurationException(sprintf(
                                'The text representation format "%s" have '
                                . 'a cyclic dependency "%s".',
                                $depName,
                                implode(' -> ', $depItems)
                            ));
                        }
                        $continue = true;
                    }
                }
            }
        }
    }
}
