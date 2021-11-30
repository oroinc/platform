<?php

namespace Oro\Bundle\EntityConfigBundle\EntityConfig;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for attribute scope.
 */
class AttributeFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'attribute';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->booleanNode('enabled')
            ->end()
            ->booleanNode('is_attribute')
                ->info('`boolean` must be set to ‘true’ to enable the ‘attribute’ functionality.')
                ->defaultFalse()
            ->end()
            ->booleanNode('is_system')
                ->info('`boolean` if set to true, the field is treated as a built-in, which means that it cannot ' .
                'be modified or removed via the UI.')
                ->defaultFalse()
            ->end()
            ->booleanNode('searchable')
                ->info('`boolean` controls whether attribute content can be searched for in the storefront.')
                ->defaultFalse()
            ->end()
            ->booleanNode('filterable')
                ->info('`boolean` controls whether the attribute can be filtered.')
                ->defaultFalse()
            ->end()
            ->scalarNode('filter_by')
                ->info('`string` defines the type of filtering to be applied to the attribute. It is applied only ' .
                'to those fields that have string representation in the search index. This parameter can have the ' .
                'following values: ‘exact_value’, ‘fulltext_search’.')
                ->defaultValue('exact_value')
            ->end()
            ->booleanNode('sortable')
                ->info('`boolean` controls if the attribute can be sorted.')
                ->defaultFalse()
            ->end()
            ->booleanNode('visible')
            ->end()
            ->scalarNode('field_name')
                ->info('`string` defines an attribute field name.')
            ->end()
        ;
    }
}
