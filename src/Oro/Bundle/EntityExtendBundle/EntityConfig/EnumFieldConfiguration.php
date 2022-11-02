<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for enum scope.
 */
class EnumFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'enum';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('enum_code')
                ->info('`string` sets the code name of the options list to the field.')
            ->end()
            ->scalarNode('enum_locale')
                ->info('`string` the locale name in which an enum name and options labels are entered. This is a ' .
                'temporary attribute used to allow creating an enum on a field edit page. As part of the schema ' .
                'update procedure, the value of this attribute is removed.')
            ->end()
            ->scalarNode('enum_name')
                ->info('`string` the name of an enum linked to a field. This is a temporary attribute used to allow ' .
                'creating an enum on a field edit page. The value of this attribute is used as a label for an entity ' .
                'that is used to store enum values, and then as part of the field reference update procedure, ' .
                'it is removed.')
            ->end()
            ->node('enum_public', 'normalized_boolean')
                ->info('`boolean` indicates whether an enum is public or not. This temporary attribute is used to ' .
                'create/edit an enum on a field edit page. As part of the schema update procedure, the value of this ' .
                'attribute is moved to the entity.enum.public attribute. This flag cannot be changed for system ' .
                'enums (owner=’system’).')
            ->end()
            ->arrayNode('enum_options')
                ->info('`array` the list of enum values. This temporary attribute is used to create/edit an enum ' .
                'on a field edit page. As part of the schema update procedure, the value of this attribute is moved ' .
                'to a table that is used to store enum values.')
                ->prototype('variable')->end()
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` this attribute can be used to prohibit changing the enum state (no matter ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
        ;
    }
}
