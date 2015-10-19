<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

class EntityFieldDataConverter extends ConfigurableTableDataConverter implements ContextAwareInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (empty($importedRecord['entity:id'])) {
            $importedRecord['entity:id'] = (int)$this->context->getOption('entity_id');
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return [
            'fieldName', 'is_serialized', 'type', 'entity.label', 'entity.description', 'importexport.header',
            'importexport.order', 'importexport.identity', 'importexport.excluded', 'email.available_in_template',
            'datagrid.is_visible', 'form.is_enabled', 'view.is_displayable', 'view.priority', 'search.searchable',
            'datagrid.show_filter', 'dataaudit.auditable', 'attachment.maxsize',
            'enum.enum_options.0.label', 'enum.enum_options.0.is_default',
            'enum.enum_options.1.label', 'enum.enum_options.1.is_default',
            'enum.enum_options.2.label', 'enum.enum_options.2.is_default',
        ];
    }
}
