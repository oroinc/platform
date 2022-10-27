<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection;

use Oro\Bundle\BatchBundle\Step\ItemStep;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration tree for batch jobs.
 */
class BatchJobsConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('connector');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('name')->end()
                ->arrayNode('jobs')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('title')->end()
                            ->scalarNode('type')->end()
                            ->arrayNode('steps')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('title')->end()
                                        ->scalarNode('class')
                                            ->defaultValue(ItemStep::class)
                                        ->end()
                                        ->arrayNode('services')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->arrayNode('parameters')
                                            ->prototype('scalar')->end()
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
