<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

class PropertyPathTitleDataConverter extends ConfigurableTableDataConverter
{
    /**
     * @var string
     */
    protected $relationDelimiter = '.';

    /**
     * {@inheritdoc}
     */
    protected function getFieldHeader($entityName, $field)
    {
        return $field['name'];
    }
}
