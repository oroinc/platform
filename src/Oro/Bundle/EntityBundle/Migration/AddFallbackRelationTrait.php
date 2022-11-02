<?php

namespace Oro\Bundle\EntityBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;

/**
 * This trait is designed to add relations between existing entities and EntityFieldFallbackValue
 */
trait AddFallbackRelationTrait
{
    /**
     * @param Schema $schema
     * @param ExtendExtension $extendExtension
     * @param string $tableName
     * @param string $fieldName
     * @param string $label
     * @param array $fallbackList
     * @param array $options
     */
    protected function addFallbackRelation(
        Schema $schema,
        ExtendExtension $extendExtension,
        $tableName,
        $fieldName,
        $label,
        $fallbackList,
        array $options = []
    ) {
        $table = $schema->getTable($tableName);
        $fallbackTable = $schema->getTable('oro_entity_fallback_value');
        $extendExtension->addManyToOneRelation(
            $schema,
            $table,
            $fieldName,
            $fallbackTable,
            'id',
            array_merge_recursive([
                'entity' => [
                    'label' => $label,
                ],
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                ],
                'form' => [
                    'is_enabled' => false,
                ],
                'view' => [
                    'is_displayable' => false,
                ],
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
                ],
                'fallback' => [
                    'fallbackList' => $fallbackList
                ]
            ], $options)
        );
    }
}
