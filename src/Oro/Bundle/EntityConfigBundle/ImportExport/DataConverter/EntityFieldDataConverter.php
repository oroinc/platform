<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

class EntityFieldDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{
    /** @var FieldTypeProvider */
    protected $fieldTypeProvider;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ContextInterface */
    protected $context;

    /** @var array */
    protected $excludedFields = ['enum.enum_options', 'attachment.attachment'];

    /**
     * @param FieldTypeProvider $fieldTypeProvider
     * @param ConfigManager $configManager
     */
    public function __construct(FieldTypeProvider $fieldTypeProvider, ConfigManager $configManager)
    {
        $this->fieldTypeProvider = $fieldTypeProvider;
        $this->configManager = $configManager;
    }

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
        $header = ['fieldName', 'is_serialized', 'type'];

        foreach ($this->fieldTypeProvider->getSupportedFieldTypes() as $fieldType) {
            $properties = $this->fieldTypeProvider->getFieldProperties($fieldType);

            foreach ($properties as $scope => $fields) {
                foreach ($fields as $code => $config) {
                    $field = sprintf('%s.%s', $scope, $code);

                    if (in_array($field, $this->excludedFields, true) || in_array($field, $header, true)) {
                        continue;
                    }
                    $header[] = $field;
                }
            }
        }

        $header = array_merge(
            $header,
            [
                'enum.enum_options.0.label',
                'enum.enum_options.0.is_default',
                'enum.enum_options.1.label',
                'enum.enum_options.1.is_default',
                'enum.enum_options.2.label',
                'enum.enum_options.2.is_default'
            ]
        );

        return $header;
    }

    /**
     * {@inheritdoc}
     */
    protected function fillEmptyColumns(array $header, array $data)
    {
        $dataDiff = array_diff(array_keys($data), $header);
        $data = array_diff_key($data, array_flip($dataDiff));

        return parent::fillEmptyColumns($header, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [];
    }
}
