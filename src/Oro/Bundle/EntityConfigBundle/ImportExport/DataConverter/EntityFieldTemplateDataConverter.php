<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

/**
 * Data converter that converts entity field data to the format which is used to deserialize the entity from the array.
 */
class EntityFieldTemplateDataConverter extends AbstractFieldTemplateDataConverter
{
    /**
     * @param $fieldType
     * @return array
     */
    protected function getFieldProperties($fieldType)
    {
        $fieldProperties = parent::getFieldProperties($fieldType);
        unset($fieldProperties['attribute']);

        return $fieldProperties;
    }
}
