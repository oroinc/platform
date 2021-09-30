<?php

namespace Oro\Bundle\EntityBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\FieldConfigInterface;
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
            ->node('is_visible', 'normalized_boolean')
                ->info('`boolean` if set to true, the field is displayed as the datagrid column.')
            ->end()
            ->node('show_filter', 'normalized_boolean')
                ->info('`boolean` if set to true, the field is displayed in the datagrid filter.')
            ->end()
            ->scalarNode('order')
                ->info('`integer` enables you to change datagrid column position.')
            ->end()
        ;
    }
}
