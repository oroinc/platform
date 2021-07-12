<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration parameters recognized by SearchBundle.
 */
class Configuration implements ConfigurationInterface
{
    public const DEFAULT_ENGINE = 'orm';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_search');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('engine')
                    ->cannotBeEmpty()
                    ->defaultValue(self::DEFAULT_ENGINE)
                ->end()
                ->arrayNode('required_plugins')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('engine_parameters')
                    ->prototype('variable')->end()
                ->end()
                ->booleanNode('log_queries')
                    ->defaultFalse()
                ->end()
                ->scalarNode('item_container_template')
                    ->defaultValue('@OroSearch/Datagrid/itemContainer.html.twig')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
