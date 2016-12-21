<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

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
