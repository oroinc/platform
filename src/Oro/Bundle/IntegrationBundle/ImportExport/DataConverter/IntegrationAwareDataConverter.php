<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Provides common functionality for data converters that need integration channel context.
 *
 * This base class automatically injects the integration channel ID into imported records
 * when available in the import/export context. Subclasses should extend this for converters
 * that process data from integration channels.
 */
abstract class IntegrationAwareDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;

    #[\Override]
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if ($this->context && $this->context->hasOption('channel')) {
            $importedRecord['channel:id'] = $this->context->getOption('channel');
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }
}
