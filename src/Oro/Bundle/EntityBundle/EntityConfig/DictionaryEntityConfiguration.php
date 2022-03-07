<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for dictionary scope.
 */
class DictionaryEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'dictionary';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('virtual_fields')
                ->scalarPrototype()->end()
                ->info('`string[]` specifies the list of fields for which the virtual fields can be created. If it ' .
                    'is not specified, the virtual fields are created for all fields, except for the identifier ones.')
            ->end()
            ->arrayNode('search_fields')
                ->scalarPrototype()->end()
                ->info('`string[]` specifies the list of fields used for searching in the reports filter.')
            ->end()
            ->scalarNode('representation_field')
                ->info('`string` specifies the representation field used to render titles for search items in the ' .
                    'reports filter.')
            ->end()
            ->node('activity_support', 'normalized_boolean')
                ->info('`boolean` enables the “activity_support” functionality.')
            ->end()
        ;
    }
}
