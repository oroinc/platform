<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

/**
 * Data converter that converts attribute data to the format that is used to deserialize the entity from the array.
 */
class AttributeTemplateDataConverter extends AbstractFieldTemplateDataConverter
{
    /**
     * {@inheritDoc}
     */
    protected function getMainHeaders(): array
    {
        return ['fieldName', 'type'];
    }
}
