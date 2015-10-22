<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldTypeProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

class EntityFieldDataConverter extends ConfigurableTableDataConverter implements ContextAwareInterface
{
    /** @var ExtendFieldTypeProvider */
    protected $extendFieldTypeProvider;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ContextInterface */
    protected $context;

    /**
     * @param FieldHelper $fieldHelper
     * @param RelationCalculatorInterface $relationCalculator
     * @param ExtendFieldTypeProvider $extendFieldTypeProvider
     * @param ConfigManager $configManager
     */
    public function __construct(
        FieldHelper $fieldHelper,
        RelationCalculatorInterface $relationCalculator,
        ExtendFieldTypeProvider $extendFieldTypeProvider,
        ConfigManager $configManager
    ) {
        parent::__construct($fieldHelper, $relationCalculator);

        $this->extendFieldTypeProvider = $extendFieldTypeProvider;
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
     * @return array
     */
    protected function receiveBackendHeader()
    {
        return $this->backendHeader = $this->getBackendHeader();
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        $header = ['fieldName', 'is_serialized', 'type'];

        foreach ($this->extendFieldTypeProvider->getSupportedFieldTypes() as $fieldType) {
            $properties = $this->getProperties($fieldType);

            foreach ($properties as $scope => $fields) {
                foreach ($fields as $code => $config) {
                    $field = sprintf('%s.%s', $scope, $code);

                    if (!in_array($field, $header, true)) {
                        $header[] = $field;
                    }
                }
            }
        }

        return $header;
    }

    /**
     * @param string $fieldType
     * @return array
     */
    protected function getProperties($fieldType)
    {
        $configType = PropertyConfigContainer::TYPE_FIELD;
        $properties = [];

        foreach ($this->configManager->getProviders() as $provider) {
            $propertyConfig = $provider->getPropertyConfig();

            if ($propertyConfig->hasForm($configType, $fieldType)) {
                $items = $propertyConfig->getFormItems($configType, $fieldType);
                $scope = $provider->getScope();

                foreach ($items as $code => $config) {
                    if (!isset($properties[$scope][$code])) {
                        $properties[$scope][$code] = $config;
                    }
                }
            }
        }

        return $properties;
    }
}
