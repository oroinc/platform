<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class EntitiesConfigConfiguration implements ConfigurationInterface
{
    const ROOT_NODE = 'entities_config';
    const RELATION_FIELDS_NODE_MAX_LEVEL = 4;

    /** @var array */
    protected $targetTypes = array(
        Query::TYPE_TEXT,
        Query::TYPE_DECIMAL,
        Query::TYPE_INTEGER,
        Query::TYPE_DATETIME
    );

    /** @var array */
    protected $relationTypes = array(
        Indexer::RELATION_ONE_TO_ONE,
        Indexer::RELATION_ONE_TO_MANY,
        Indexer::RELATION_MANY_TO_ONE,
        Indexer::RELATION_MANY_TO_MANY
    );

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $this->getEntitiesConfigurationNode($builder);

        return $builder;
    }

    /**
     * @param TreeBuilder $builder
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    public function getEntitiesConfigurationNode(TreeBuilder $builder)
    {
        $node = $builder->root(self::ROOT_NODE);

        $node
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
                ->arrayNode('title_fields')
                    ->performNoDeepMerging()
                    ->prototype('scalar')->end()
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
                ->arrayNode('fields')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                    ->children()
                        ->scalarNode('name')->end()
                        ->enumNode('target_type')
                            ->values($this->targetTypes)
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

        return $node;
    }

    /**
     * @param int $level
     * @return ArrayNodeDefinition
     */
    protected function getRelationFieldsNodeDefinition($level = 1)
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

    /**
     * @return ArrayNodeDefinition
     */
    protected function addTargetFieldsNode()
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
            ->prototype('scalar')->end()
        ->end();

        return $targetFieldsNode;
    }
}
