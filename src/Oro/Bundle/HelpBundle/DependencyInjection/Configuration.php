<?php

namespace Oro\Bundle\HelpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_help');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('defaults')
                    ->isRequired()
                    ->children()
                        ->scalarNode('server')
                            ->cannotBeEmpty()
                            ->isRequired()
                            ->validate()
                                ->ifTrue(function ($value) {
                                    return !filter_var($value, FILTER_VALIDATE_URL);
                                })
                                ->thenInvalid('Invalid URL %s.')
                            ->end()
                        ->end()
                        ->scalarNode('prefix')->end()
                        ->scalarNode('uri')->end()
                        ->scalarNode('link')
                            ->validate()
                                ->ifTrue(function ($value) {
                                    return !filter_var($value, FILTER_VALIDATE_URL);
                                })
                                ->thenInvalid('Invalid URL %s.')
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
