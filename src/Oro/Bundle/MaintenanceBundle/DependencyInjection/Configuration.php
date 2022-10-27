<?php

namespace Oro\Bundle\MaintenanceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_maintenance');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('authorized')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('path')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('host')
                            ->defaultNull()
                        ->end()
                        ->variableNode('ips')
                            ->defaultValue([])
                        ->end()
                        ->variableNode('query')
                            ->defaultValue([])
                        ->end()
                        ->variableNode('cookie')
                            ->defaultValue([])
                        ->end()
                        ->scalarNode('route')
                            ->defaultNull()
                        ->end()
                        ->variableNode('attributes')
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('driver')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->variableNode('options')
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('response')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('code')
                            ->defaultValue(503)
                        ->end()
                        ->scalarNode('status')
                            ->defaultValue('Service Temporarily Unavailable')
                        ->end()
                        ->scalarNode('exception_message')
                            ->defaultValue('Service Temporarily Unavailable')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
