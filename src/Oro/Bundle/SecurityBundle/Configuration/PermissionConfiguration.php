<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class PermissionConfiguration implements ConfigurationInterface
{
    const DEFAULT_GROUP_NAME = 'default';

    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this, $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('permission');
        $this->addNodes($root);

        return $builder;
    }

    /**
     * @param NodeDefinition $nodeDefinition
     * @return NodeDefinition
     */
    public function addNodes(NodeDefinition $nodeDefinition)
    {
        $nodeDefinition
            ->children()
                ->scalarNode('label')
                ->end()
                ->scalarNode('description')
                ->end()
                ->arrayNode('group_names')
                    ->defaultValue([static::DEFAULT_GROUP_NAME])
                    ->beforeNormalization()
                        ->always(
                            function ($groupName) {
                                return array_unique(
                                    array_map(
                                        function ($value) {
                                            return $value === '' ? static::DEFAULT_GROUP_NAME : $value;
                                        },
                                        (array) $groupName
                                    )
                                );
                            }
                        )
                    ->end()
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->booleanNode('apply_to_all')
                    ->defaultValue(true)
                ->end()
                ->arrayNode('apply_to_entities')
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('exclude_entities')
                    ->prototype('scalar')
                    ->end()
                ->end()
            ->end();

        return $nodeDefinition;
    }
}
