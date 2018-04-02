<?php

namespace Oro\Bundle\EntityBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;

trait AddFallbackRelationTrait
{
    /**
     * @param Schema $schema
     * @param ExtendExtension $extendExtension
     * @param string $tableName
     * @param string $fieldName
     * @param string $label
     * @param array $fallbackList
     * @param string|null $fallbackType
     */
    protected function addFallbackRelation(
        Schema $schema,
        ExtendExtension $extendExtension,
        $tableName,
        $fieldName,
        $label,
        $fallbackList,
        $fallbackType = null
    ) {
        $table = $schema->getTable($tableName);
        $fallbackTable = $schema->getTable('oro_entity_fallback_value');

        $fallbackConfig = ['fallbackList' => $fallbackList];
        if ($fallbackType) {
            $fallbackConfig['fallbackType'] = $fallbackType;
        }

        $extendExtension->addManyToOneRelation(
            $schema,
            $table,
            $fieldName,
            $fallbackTable,
            'id',
            [
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
                'fallback' => $fallbackConfig
            ]
        );
    }
}
