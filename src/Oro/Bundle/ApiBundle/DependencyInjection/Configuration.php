<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

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
        $rootNode    = $treeBuilder->root('oro_api');

        $rootNode->children()
            ->arrayNode('actions')
                ->info('A definition of Data API actions')
                ->example(
                    [
                        'get' => [
                            'processing_groups' => [
                                'load_data' => [
                                    'priority' => -10
                                ],
                                'normalize_data' => [
                                    'priority' => -20
                                ]
                            ]
                        ]
                    ]
                )
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->arrayNode('processing_groups')
                            ->info('A list of groups by which child processors can be split')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('priority')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('filters')
                ->info('A definition of filters')
                ->example(
                    [
                        'string' => [
                            'class' => 'Oro\Bundle\ApiBundle\Filter\ComparisonFilter',
                            'supported_operators' => ['=', '<>']
                        ]
                    ]
                )
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('class')
                            ->cannotBeEmpty()
                            ->defaultValue('Oro\Bundle\ApiBundle\Filter\ComparisonFilter')
                        ->end()
                        ->arrayNode('supported_operators')
                            ->prototype('scalar')->end()
                            ->cannotBeEmpty()
                            ->defaultValue(['=', '<>'])
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('form_types')
                ->info('The form types that can be reused in Data API')
                ->example(['form.type.form', 'form.type.integer', 'form.type.text'])
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('form_type_extensions')
                ->info('The form type extensions that can be reused in Data API')
                ->example(['form.type_extension.form.http_foundation', 'form.type_extension.form.validator'])
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('form_type_guessers')
                ->info('The form type guessers that can be reused in Data API')
                ->example(['form.type_guesser.validator'])
                ->prototype('scalar')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
