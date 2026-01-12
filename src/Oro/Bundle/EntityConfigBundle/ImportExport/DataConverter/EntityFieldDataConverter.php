<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Converts entity field data between import and export formats.
 *
 * This converter handles the transformation of entity field configuration data during import/export operations,
 * automatically injecting the entity identifier from the import context when not provided in the imported record.
 */
class EntityFieldDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{
    /** @var ContextInterface */
    protected $context;

    #[\Override]
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (empty($importedRecord['entity:id'])) {
            $importedRecord['entity:id'] = (int)$this->context->getOption('entity_id');
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \RuntimeException('Normalization is not implemented!');
    }

    #[\Override]
    protected function getHeaderConversionRules()
    {
        // CSV headers are used as is during import, we do not need extra rules
        return [];
    }
}
