<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

/**
 * Data converter that uses property names as field headers for import/export.
 *
 * This converter extends {@see ConfigurableTableDataConverter} to customize the field
 * header generation during import/export operations. Instead of using complex
 * field labels, it uses the property name directly as the header, providing a
 * simpler and more direct mapping between data and entity properties.
 */
class PropertyPathTitleDataConverter extends ConfigurableTableDataConverter
{
    /**
     * @var string
     */
    protected $relationDelimiter = '.';

    #[\Override]
    protected function getFieldHeader($entityName, $field)
    {
        return $field['name'];
    }
}
