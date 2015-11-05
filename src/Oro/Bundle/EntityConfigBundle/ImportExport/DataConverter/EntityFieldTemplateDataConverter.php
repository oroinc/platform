<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

class EntityFieldTemplateDataConverter extends EntityFieldDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return array_merge(
            parent::getBackendHeader(),
            [
                'enum.enum_options.0.label',
                'enum.enum_options.0.is_default',
                'enum.enum_options.1.label',
                'enum.enum_options.1.is_default',
                'enum.enum_options.2.label',
                'enum.enum_options.2.is_default'
            ]
        );
    }
}
