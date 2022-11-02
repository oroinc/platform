<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const DEFAULT_ENGINE_DSN = 'orm:';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_search');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('engine_dsn')
                    ->cannotBeEmpty()
                    ->defaultValue(self::DEFAULT_ENGINE_DSN)
                ->end()
                ->arrayNode('required_plugins')
                    ->prototype('array')->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('required_attributes')
                    ->info(
                        'Contains an array of the required Elasticsearch attribute values to be checked on'
                        . ' platform install or upgrade.' . PHP_EOL
                        . 'The array\'s key determines the attribute name.'
                    )->useAttributeAsKey('attribute_name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('value')
                                ->info('Contains a proper Elasticsearch attribute value.')
                                ->isRequired()
                            ->end()
                            ->scalarNode('err_message')
                                ->info(
                                    'Should contain a comprehensive message displayed'
                                    . ' if Elasticsearch attribute value validation has failed.'
                                )
                            ->end()
                        ->end()
                    ->end()
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
