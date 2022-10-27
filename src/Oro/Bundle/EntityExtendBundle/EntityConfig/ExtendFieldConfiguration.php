<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for extend scope.
 */
class ExtendFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'extend';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('owner')
                ->defaultValue('System')
            ->end()
            ->scalarNode('state')
                ->defaultValue('Active')
            ->end()
            ->node('is_extend', 'normalized_boolean')
                ->info('`boolean` switches to the ‘extend’ functionality.')
                ->defaultFalse()
            ->end()
            ->scalarNode('length')
                ->defaultValue(255)
            ->end()
            ->scalarNode('precision')
                ->defaultValue(10)
            ->end()
            ->scalarNode('scale')
                ->defaultValue(2)
            ->end()
            ->node('is_deleted', 'normalized_boolean')
                ->defaultFalse()
            ->end()
            ->node('bidirectional', 'normalized_boolean')
            ->end()
            ->scalarNode('relation_key')
                ->info('`string` can be built by the ExtendHelper::buildRelationKey method. The attribute is in the ' .
                'following format: ‘relation_type’, ‘owning_entity’, ‘target_entity’,’field_name_in_owning_entity’.')
            ->end()
            ->node('without_default', 'normalized_boolean')
                ->info('`boolean` indicates whether a relation has default value or not. Applicable only to ' .
                'many-to-many or one-to-many relations. If not specified or FALSE, the relation has the default value.')
            ->end()
            ->scalarNode('target_entity')
                ->info('`string` the target entity class name.')
            ->end()
            ->scalarNode('target_field')
                ->info('`string` the field name in the target entity used to show a related entity. This attribute ' .
                'is applicable to many-to-one relations.')
            ->end()
            ->arrayNode('target_grid')
                ->info('`string[]` the list of field names in the target entity used to show a related entity in ' .
                'the grid. This attribute is applicable to many-to-many and one-to-many relations.')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('target_title')
                ->info('`string[]` the list of field names in the target entity used to show the title of a related ' .
                'entity. This attribute is applicable to many-to-many and one-to-many relations.')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('target_detailed')
                ->info('`string[]` the list of field names in the target entity used to show detailed information ' .
                'about a related entity. This attribute is applicable to many-to-many and one-to-many relations.')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('cascade')
                ->info('`string` The names of persistence operations to cascade on the relation. Possible values ' .
                'are: ‘persist’, ‘remove’, ‘detach’, ‘merge’, ‘refresh’, ‘all’. Note that the ‘detach’ operation ' .
                'for many-to-one and one-to-many relations is applied by default and this cannot be changed through ' .
                'the configuration. This attribute is applicable to any type of relations. See Doctrine’s ' .
                'documentation for more details.')
                ->scalarPrototype()->end()
            ->end()
            ->scalarNode('fetch')
                ->info('`string` the type of fetch mode for the relation. Possible values are ‘lazy’, ‘extra_lazy’, ' .
                'and ‘eager’.')
            ->end()
            ->node('nullable', 'normalized_boolean')
                ->defaultTrue()
            ->end()
            ->scalarNode('on_delete')
                ->info('`string` defines what happens with related rows ‘on delete’. Possible value are: ‘CASCADE’, ' .
                '‘SET NULL’, ‘RESTRICT’.')
            ->end()
            ->node('orphanRemoval', 'normalized_boolean')
                ->info('`boolean` there is concept of cascading that is relevant only when removing entities from ' .
                'collections. If an Entity of type A contains references to a privately owned Entity B, and if the ' .
                'reference from A to B is removed, then entity B should also be removed as it is no longer used. ' .
                'OrphanRemoval works with one-to-one, one-to-many and many-to-many associations. See Doctrine’s ' .
                'documentation for more details.')
            ->end()
            ->scalarNode('default')
            ->end()
            ->node('is_serialized', 'normalized_boolean')
                ->info('`boolean` if set to true, the field data is saved in the serialized_data column without ' .
                'doctrine schema update.')
                ->defaultFalse()
            ->end()
            ->scalarNode('column_name')->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` this attribute can be used to prohibit changing the extend state (no matter ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
        ;
    }
}
