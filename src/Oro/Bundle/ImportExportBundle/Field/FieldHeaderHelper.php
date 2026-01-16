<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Field;

use Oro\Bundle\EntityBundle\Helper\FieldHelper as EntityFieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

/**
 * Provides helper methods for building import/export field headers.
 */
class FieldHeaderHelper
{
    public function __construct(
        protected readonly EntityFieldHelper $entityFieldHelper
    ) {
    }

    /**
     * Builds a relation field header in the format used by the import/export system.
     * For example, "Category.ID" for Product's category field pointing to Category's id field.
     *
     * @param string $entityClass The entity class containing the relation field (e.g., Product::class)
     * @param string $relationFieldName The name of the relation field (e.g., 'category')
     * @param string $relatedEntityClass The related entity class (e.g., Category::class)
     * @param string $relatedFieldName The field name in the related entity (e.g., 'id')
     * @param string $delimiter The delimiter to use between field names (default: '.')
     * @return string The formatted header (e.g., "Category.ID")
     */
    public function buildRelationFieldHeader(
        string $entityClass,
        string $relationFieldName,
        string $relatedEntityClass,
        string $relatedFieldName,
        string $delimiter = '.'
    ): string {
        // Get the relation field label from the entity
        $entityFields = $this->entityFieldHelper->getEntityFields(
            $entityClass,
            EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_TRANSLATE
        );

        $relationFieldLabel = null;
        foreach ($entityFields as $field) {
            if ($field['name'] === $relationFieldName) {
                $relationFieldLabel = $field['label'];
                break;
            }
        }

        // Get the related field label from the related entity
        $relatedEntityFields = $this->entityFieldHelper->getEntityFields(
            $relatedEntityClass,
            EntityFieldProvider::OPTION_TRANSLATE
        );

        $relatedFieldLabel = null;
        foreach ($relatedEntityFields as $field) {
            if ($field['name'] === $relatedFieldName) {
                $relatedFieldLabel = $field['label'];
                break;
            }
        }

        // Build the header in the format "RelationLabel.RelatedFieldLabel"
        return ($relationFieldLabel ?: \ucfirst($relationFieldName))
            . $delimiter
            . ($relatedFieldLabel ?: \ucfirst($relatedFieldName));
    }
}
