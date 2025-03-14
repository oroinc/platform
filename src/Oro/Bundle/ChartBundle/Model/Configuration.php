<?php

namespace Oro\Bundle\ChartBundle\Model;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/charts.yml" files.
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE_NAME = 'charts';

    /**
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE_NAME);
        $treeBuilder->getRootNode()
            ->info('Configuration of charts')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->info('The label of chart')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->arrayNode('data_schema')
                        ->info('Schema of chart data fields')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('label')
                                    ->info('Name of chart data field')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                                ->scalarNode('default_type')
                                    ->info('Default type of chart data field')
                                    ->cannotBeEmpty()
                                    ->defaultValue('string')
                                ->end()
                                ->scalarNode('type')
                                    ->info('Type for axis render. Currency, month etc.')
                                ->end()
                                ->scalarNode('name')
                                    ->info('Label of chart data field')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                                ->booleanNode('required')
                                    ->info('Is chart data field required')
                                    ->isRequired()
                                ->end()
                                ->arrayNode('type_filter')
                                    ->info('Filter type for fields')
                                    ->prototype('variable')
                                    ->end()
                                ->end()
                                ->scalarNode('field_name')
                                    ->info('Predefined field name')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('settings_schema')
                        ->info('Schema of chart settings fields')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('name')
                                    ->info('Name of chart data field')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                                ->scalarNode('label')
                                    ->info('Name of chart settings field')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                                ->scalarNode('type')
                                    ->info('Form type of chart settings field')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                                ->arrayNode('options')
                                    ->info('Options of form type of chart settings field')
                                    ->prototype('variable')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('default_settings')
                        ->info('Default settings of chart')
                        ->prototype('variable')
                        ->end()
                    ->end()
                    ->scalarNode('data_transformer')
                        ->info('Chart data transformer')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('template')
                        ->info('Template of chart')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->arrayNode('xaxis')
                        ->info('Flotr2 xaxis options. See http://www.humblesoftware.com/flotr2/documentation')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->variableNode('mode')
                                ->info('can be "time" or "normal"')
                                ->defaultValue('normal')
                            ->end()
                            ->integerNode('noTicks')
                                ->info('number of ticks for automatically generated ticks')
                                ->defaultValue(5)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
