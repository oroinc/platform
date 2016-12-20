<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

class AttributeTemplateDataConverter extends AbstractFieldTemplateDataConverter
{
    /**
     * @return array
     */
    protected function getMainHeaders()
    {
        return ['fieldName', 'type'];
    }
}
