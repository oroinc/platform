<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for extend scope.
 */
class ExtendEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'extend';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('owner')
                ->info('`string` can have the following values:' . "\n" .
                ' - ExtendScope::OWNER_CUSTOM - The property is user-defined, and the core system should handle ' .
                'how the property appears in grids, forms, etc. (if not configured otherwise).' . "\n" .
                ' - ExtendScope::OWNER_SYSTEM - Nothing is rendered automatically, and you must explicitly specify ' .
                'how to show the property in different parts of the system (grids, forms, views, etc.).')
                ->defaultValue('System')
            ->end()
            ->node('is_extend', 'normalized_boolean')
                ->info('`boolean` if true the config entity able to extend')
                ->defaultFalse()
            ->end()
            ->scalarNode('state')
                ->info('`string` the state of the extend config field. All available state you can find in ' .
                '`ExtendScope`(https://github.com/laboro/dev/blob/master/package/platform/src/Oro/Bundle/'.
                'EntityExtendBundle/EntityConfig/ExtendScope.php)')
                ->defaultValue('Active')
            ->end()
            ->node('is_deleted', 'normalized_boolean')
                ->info('`boolean` if true the config entity able to delete')
                ->defaultFalse()
            ->end()
            ->scalarNode('unique_key')
                ->info('`string` name of unique key')
            ->end()
            ->arrayNode('index')
                ->info('`string[]` list of index field of the entity. All available index state you can find in ' .
                '`IndexScope`(https://github.com/laboro/dev/blob/master/package/platform/src/Oro/Bundle/'.
                'EntityBundle/EntityConfig/IndexScope.php)')
                ->scalarPrototype()->end()
            ->end()
            ->node('upgradeable', 'normalized_boolean')
                ->info('`boolean` if true the extend config entity able to update')
                ->defaultFalse()
            ->end()
            ->arrayNode('relation')
                ->info('`array` contain information about relation of the entity')
                ->prototype('variable')->end()
            ->end()
            ->scalarNode('table')
                ->info('`string` is the table name for a custom entity. This is optional attribute. If it is not ' .
                'specified, the table name is generated automatically.')
            ->end()
            ->scalarNode('inherit')
                ->info('`string` is the parent class name. You are not usually requires to specify this attribute ' .
                'as it is calculated automatically for regular extend and custom entities. An example of an entity ' .
                'where this attribute is used is EnumValue.')
            ->end()
            ->arrayNode('schema')
                ->info('`array` contain information about structure and entity class of the extend')
                ->ignoreExtraKeys(false)
            ->end()
            ->arrayNode('pk_columns')
                ->info('`string[]` list of Primary Keys column name')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('pending_changes')
                ->info('`object` when a user changes something that requires schema update, this change is not ' .
                'applied to the configuration, but is stored into “pending_changes” as changeset. The format of ' .
                'changeset is [‘scope’ => [‘field’ => [‘oldValue’, ‘newValue’], …], …].' . "\n" .
                'Let’s assume that a user has an active activity email and changes it to a task. In this case, the ' .
                'value of pending changes would be like in the example.')
                ->example([
                    'activity' => [
                        'activities' => [
                            ['Oro\Bundle\EmailBundle\Entity\Email'],
                            ['Oro\Bundle\TaskBundle\Entity\Task'],
                        ],
                    ]
                ])
                ->ignoreExtraKeys(false)
            ->end()
            ->node('is_serialized', 'normalized_boolean')
                ->info('`boolean` if TRUE then field data will be saved in serialized_data column without doctrine ' .
                'schema update.')
            ->end()
        ;
    }
}
