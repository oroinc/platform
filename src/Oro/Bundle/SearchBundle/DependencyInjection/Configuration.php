<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Engine\Indexer;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_ENGINE                 = 'orm';
    const RELATION_FIELDS_NODE_MAX_LEVEL = 4;

    protected $targetTypes   = array(
        Query::TYPE_TEXT,
        Query::TYPE_DECIMAL,
        Query::TYPE_INTEGER,
        Query::TYPE_DATETIME
    );

    protected $relationTypes = array(
        Indexer::RELATION_ONE_TO_ONE,
        Indexer::RELATION_ONE_TO_MANY,
        Indexer::RELATION_MANY_TO_ONE,
        Indexer::RELATION_MANY_TO_MANY
    );

    /**
     * Bundle configuration structure
     *
     * @return TreeBuilder
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_search');

        $rootNode
            ->children()
                ->scalarNode('engine')
                    ->cannotBeEmpty()
                    ->defaultValue(self::DEFAULT_ENGINE)
                ->end()
                ->arrayNode('engine_parameters')
                    ->prototype('variable')->end()
                ->end()
                ->booleanNode('log_queries')
                    ->defaultFalse()
                ->end()
                ->booleanNode('realtime_update')
                    ->defaultTrue()
                ->end()
                ->scalarNode('item_container_template')
                    ->defaultValue('OroSearchBundle:Datagrid:itemContainer.html.twig')
                ->end()
                ->arrayNode('entities_config')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('alias')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('label')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('title_fields')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('route')
                            ->children()
                                ->scalarNode('name')->end()
                                ->arrayNode('parameters')
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
                        ->arrayNode('fields')
                            ->prototype('array')
                            ->children()
                                ->scalarNode('name')->end()
                                ->enumNode('target_type')
                                    ->values($this->targetTypes)
                                ->end()
                                ->arrayNode('target_fields')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->scalarNode('getter')->end()
                                ->enumNode('relation_type')
                                    ->values($this->relationTypes)
                                ->end()
                                ->scalarNode('relation_class')->end()
                                ->append($this->getRelationFieldsNodeDefinition())
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function getRelationFieldsNodeDefinition($level = 1)
    {
        $nodeBuilder = new NodeBuilder();
        $relationFieldsNode = $nodeBuilder->arrayNode('relation_fields');

        if ($level < self::RELATION_FIELDS_NODE_MAX_LEVEL) {
            $relationFieldsNode
                ->prototype('array')
                    ->children()
                        ->scalarNode('name')->end()
                        ->enumNode('target_type')
                            ->values($this->targetTypes)
                        ->end()
                        ->arrayNode('target_fields')
                            ->prototype('scalar')->end()
                        ->end()
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
}
