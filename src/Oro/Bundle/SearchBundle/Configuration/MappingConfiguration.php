<?php

declare(strict_types=1);

namespace Oro\Bundle\SearchBundle\Configuration;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/search.yml" files.
 */
class MappingConfiguration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'search';

    private const int RELATION_FIELDS_NODE_MAX_LEVEL = 4;

    protected array $targetTypes = [
        Query::TYPE_TEXT,
        Query::TYPE_DECIMAL,
        Query::TYPE_INTEGER,
        Query::TYPE_DATETIME
    ];

    protected array $relationTypes = [
        Indexer::RELATION_ONE_TO_ONE,
        Indexer::RELATION_ONE_TO_MANY,
        Indexer::RELATION_MANY_TO_ONE,
        Indexer::RELATION_MANY_TO_MANY
    ];

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->scalarNode('alias')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('label')
                    ->defaultNull()
                ->end()
                ->booleanNode('synonyms_enabled')
                    ->defaultFalse()
                    ->info('Enables search synonyms support for the entity index (back-office Elasticsearch engine)')
                ->end()
                ->arrayNode('route')
                    ->children()
                        ->scalarNode('name')->end()
                        ->arrayNode('parameters')
                            ->performNoDeepMerging()
                            ->prototype('variable')->end()
                        ->end()
                    ->end()
                ->end()
                ->enumNode('mode')
                    ->values([Mode::NORMAL, Mode::ONLY_DESCENDANTS, Mode::WITH_DESCENDANTS])
                    ->defaultValue(Mode::NORMAL)
                    ->info('Defines behavior for entities with inheritance hierarchy')
                ->end()
                ->scalarNode('search_template')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('acl_permission')
                    ->validate()->ifNull()->thenUnset()->end()
                ->end()
                ->arrayNode('fields')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                    ->children()
                        ->scalarNode('name')->end()
                        ->enumNode('target_type')
                            ->values($this->targetTypes)
                        ->end()
                        ->booleanNode('target_fulltext')
                            ->defaultTrue()
                        ->end()
                        ->append($this->addTargetFieldsNode())
                        ->scalarNode('getter')->end()
                        ->enumNode('relation_type')
                            ->values($this->relationTypes)
                        ->end()
                        ->scalarNode('relation_class')->end()
                        ->append($this->getRelationFieldsNodeDefinition())
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function getRelationFieldsNodeDefinition(int $level = 1): ArrayNodeDefinition
    {
        $nodeBuilder = new NodeBuilder();
        $relationFieldsNode = $nodeBuilder->arrayNode('relation_fields');

        if ($level < self::RELATION_FIELDS_NODE_MAX_LEVEL) {
            $relationFieldsNode
                ->useAttributeAsKey('name', false)
                ->prototype('array')
                    ->children()
                        ->scalarNode('name')->end()
                        ->enumNode('target_type')
                            ->values($this->targetTypes)
                        ->end()
                        ->booleanNode('target_fulltext')
                            ->defaultTrue()
                        ->end()
                        ->append($this->addTargetFieldsNode())
                        ->enumNode('relation_type')
                            ->values($this->relationTypes)
                        ->end()
                        ->append($this->getRelationFieldsNodeDefinition($level + 1))
                    ->end()
                    ->validate()
                        ->ifTrue(function ($value) {
                            return (!empty($value['relation_type']) && empty($value['relation_fields']))
                                || (!empty($value['relation_fields']) && empty($value['relation_type']));
                        })
                        ->thenInvalid('Both or none of relation_type and relation_fields should be specified for field')
                    ->end()
                ->end();
        }

        return $relationFieldsNode;
    }

    protected function addTargetFieldsNode(): ArrayNodeDefinition
    {
        $nodeBuilder = new NodeBuilder();

        $targetFieldsNode = $nodeBuilder->arrayNode('target_fields');
        $targetFieldsNode
            ->validate()
                ->always(
                    function ($value) {
                        // Reset array keys because array_unique could make holes in keys
                        return array_values(array_unique($value));
                    }
                )
            ->end()
            ->prototype('scalar')->end();

        return $targetFieldsNode;
    }
}
