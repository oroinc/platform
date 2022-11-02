<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for datagrid scope.
 */
class DatagridFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'datagrid';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->integerNode('is_visible')
                ->info('`integer` controls field visibility options for datagrid.')
                ->defaultValue(DatagridScope::IS_VISIBLE_TRUE)
            ->end()
            ->node('show_filter', 'normalized_boolean')
                ->info('`boolean` if set to true, the field is displayed in the datagrid filter.')
            ->end()
            ->scalarNode('order')
                ->info('`integer` enables you to change datagrid column position.')
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` this attribute can be used to prohibit changing the datagrid state (no matter ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
        ;
    }
}
