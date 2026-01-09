<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

/**
 * Defines the contract for converting data between export and import formats.
 *
 * Implementations handle the transformation of entity data between complex internal
 * representations and flat export formats (e.g., CSV, Excel), managing the conversion
 * of nested structures and relationships into a tabular format suitable for export,
 * and vice versa for import operations.
 */
interface DataConverterInterface
{
    /**
     * Convert complex data to export plain format
     *
     * @param array $exportedRecord
     * @param boolean $skipNullValues
     * @return array
     */
    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true);

    /**
     * Convert plain data to import complex representation
     *
     * @param array $importedRecord
     * @param boolean $skipNullValues
     * @return array
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true);
}
