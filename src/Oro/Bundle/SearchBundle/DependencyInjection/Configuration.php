<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_ENGINE = 'orm';

    /**
     * Bundle configuration structure
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_search');

        $targetTypes   = array(
            Query::TYPE_TEXT,
            Query::TYPE_DECIMAL,
            Query::TYPE_INTEGER,
            Query::TYPE_DATETIME
        );
        $relationTypes = array(
            Indexer::RELATION_ONE_TO_ONE,
            Indexer::RELATION_ONE_TO_MANY,
            Indexer::RELATION_MANY_TO_ONE,
            Indexer::RELATION_MANY_TO_MANY
        );

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
                        ->scalarNode('search_template')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('fields')
                            ->prototype('array')
                            ->children()
                                ->scalarNode('name')->end()
                                ->enumNode('target_type')
                                    ->values($targetTypes)
                                ->end()
                                ->arrayNode('target_fields')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->scalarNode('getter')->end()
                                ->enumNode('relation_type')
                                    ->values($relationTypes)
                                ->end()
                                ->scalarNode('relation_class')->end()
                                ->arrayNode('relation_fields')
                                    ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')->end()
                                        ->enumNode('target_type')
                                            ->values($targetTypes)
                                        ->end()
                                        ->arrayNode('target_fields')
                                            ->prototype('scalar')->end()
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
