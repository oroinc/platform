<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema of data mapping configuration for complex data import and export.
 */
class ComplexDataMappingConfiguration implements ConfigurationInterface
{
    public function __construct(
        private readonly string $rootName
    ) {
    }

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder($this->rootName);
        $treeBuilder->getRootNode()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('target_type')->isRequired()->cannotBeEmpty()->end()
                    ->booleanNode('collection')->end()
                    ->scalarNode('entity')->cannotBeEmpty()->end()
                    ->scalarNode('lookup_field')->cannotBeEmpty()->end()
                    ->booleanNode('ignore_not_found')->end()
                    ->arrayNode('fields')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('target_path')->cannotBeEmpty()->end()
                                ->scalarNode('value')->cannotBeEmpty()->end()
                                ->scalarNode('source')->cannotBeEmpty()->end()
                                ->scalarNode('ref')->cannotBeEmpty()->end()
                                ->scalarNode('entity_data_type')->cannotBeEmpty()->end()
                                ->scalarNode('entity_path')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->always(function ($value) {
                    if (empty($value['fields'])) {
                        unset($value['fields']);
                    }

                    return $value;
                })
            ->end();

        return $treeBuilder;
    }
}
