<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

class PermissionDefinitionConfiguration implements ConfigurationInterface
{
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
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('group_name')->end()
                ->scalarNode('description')->end()
            ->end();

        return $nodeDefinition;
    }
}
