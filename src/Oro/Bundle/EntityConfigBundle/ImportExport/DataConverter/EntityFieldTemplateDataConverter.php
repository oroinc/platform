<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

/**
 * Data converter that converts entity field data to the format that is used to deserialize the entity from the array.
 */
class EntityFieldTemplateDataConverter extends AbstractFieldTemplateDataConverter
{
    /**
     * {@inheritDoc}
     */
    protected function getFieldProperties(string $fieldType): array
    {
        $fieldProperties = parent::getFieldProperties($fieldType);
        unset($fieldProperties['attribute']);

        return $fieldProperties;
    }

    /**
     * {@inheritDoc}
     */
    protected function getMainHeaders(): array
    {
        return ['fieldName', 'type'];
    }
}
