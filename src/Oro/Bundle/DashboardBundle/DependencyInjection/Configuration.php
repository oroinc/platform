<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('oro_dashboard')
            ->children()
                ->scalarNode('default_dashboard')
                    ->info('The name of a dashboard which is displayed by default')
                    ->defaultValue('quick_launchpad')
                ->end()
                ->arrayNode('widgets')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->ignoreExtraKeys()
                        ->children()
                            ->scalarNode('route')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('route_parameters')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->scalarNode('acl')
                                ->info('The ACL ancestor')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('dashboards')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')
                                ->info('The label name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('position')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('twig')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('widgets')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->ignoreExtraKeys()
                                    ->children()
                                        ->integerNode('position')
                                            ->isRequired()
                                            ->cannotBeEmpty()
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
