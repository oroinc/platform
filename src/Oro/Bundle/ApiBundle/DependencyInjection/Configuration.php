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
                ->info('A definition of API actions')
                ->example(
                    [
                        'get' => [
                            'processor' => 'oro_api.get_list.processor',
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
                        ->scalarNode('processor')
                            ->info('An identifier of a processor service')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('context_class')
                            ->info('The name of a class represents an processing context')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
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
            ->end();

        return $treeBuilder;
    }
}
