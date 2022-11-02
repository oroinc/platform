<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/integrations.yml" files.
 */
class IntegrationConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'integrations';
    public const FORM_NODE = 'form';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode(self::FORM_NODE)
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('type')->isRequired()->end()
                                ->arrayNode('options')
                                    ->prototype('variable')->end()
                                ->end()
                                ->integerNode('priority')->end()
                                ->arrayNode('applicable')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function ($v) {
                                            return [$v];
                                        })
                                    ->end()
                                    ->prototype('scalar')
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
