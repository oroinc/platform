<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for enum scope.
 */
class EnumEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'enum';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('code')
                ->info('`string` a unique identifier of this enum.')
            ->end()
            ->node('public', 'normalized_boolean')
                ->info('`boolean` indicates whether this enum is public. Public enums can be used in any extendable ' .
                    'entity, which means that you can create a field of this enum type in any entity. Private enums ' .
                    'cannot be reused.')
            ->end()
            ->node('multiple', 'normalized_boolean')
                ->info('`boolean` Indicates whether several options can be selected for this enum or it supports ' .
                    'only one selected option.')
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean or array` is used to prohibit changing the list of enum values and a public flag. ' .
                    'This means that values cannot be added or deleted, but it is still possible to update the ' .
                    'names of existing values, reorder them and change the default values. Below are examples of ' .
                    'possible values: ' . "\n" .
                    ' - false or empty array - no any restrictions' . "\n" .
                    ' - true - means that all constraints are applied, so it will not be allowed to add/delete ' .
                    'options and change ‘public’ flag' . "\n" .
                    ' - ‘add’, ‘delete’, ‘public’ - the same as true; it will not be allowed to add/delete options ' .
                    'and change ‘public’ flag' . "\n" .
                    ' - ‘delete’ - it is not allowed to delete options, but new options can be added and ‘public’ ' .
                    'can be changed')
            ->end()
            ->arrayNode('immutable_codes')
                ->info('`string[]` is an array of undetectable enum options. These options cannot be deleted but can ' .
                    'still be edited.')
                ->scalarPrototype()->end()
            ->end()
        ;
    }
}
