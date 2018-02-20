<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ActionGroupListConfiguration implements ConfigurationDefinitionInterface
{
    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this, [$configs]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('action_groups');
        $root
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->variableNode('acl_resource')->end()
                ->arrayNode('parameters')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')->end()
                            ->scalarNode('message')->end()
                            ->variableNode('default')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('conditions')
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('actions')
                    ->prototype('variable')->end()
                ->end()
            ->end()
        ->end();

        return $builder;
    }
}
