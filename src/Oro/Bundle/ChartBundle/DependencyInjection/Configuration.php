<?php

namespace Oro\Bundle\ChartBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_DATA_TRANSFORMER_SERVICE = 'oro_dashboard.data_transformer.default';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('oro_chart')
            ->info('Configuration of charts')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->info('The label of chart')
                        ->cannotBeEmpty()
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
                                ->scalarNode('name')
                                    ->info('Label of chart data field')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                                ->booleanNode('required')
                                    ->info('Is chart data field required')
                                    ->cannotBeEmpty()
                                    ->isRequired()
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
                    /** @todo Remove chart data transformer */
                    ->scalarNode('data_transformer')
                        ->info('Chart data transformer')
                        ->defaultValue(self::DEFAULT_DATA_TRANSFORMER_SERVICE)
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('template')
                        ->info('Template of chart')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
