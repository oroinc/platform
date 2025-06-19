<?php

namespace Oro\Bundle\ImportExportBundle\Processor\ComplexData;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterRegistry;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationDataAccessorInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping\ComplexDataMappingProvider;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ValueTransformer\ComplexDataValueTransformerInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;

/**
 * Handles complex data export.
 */
class ComplexDataExportProcessor implements ProcessorInterface
{
    private ?ComplexDataConverterRegistry $converterRegistry = null;

    public function __construct(
        private readonly ComplexDataMappingProvider $mappingProvider,
        private readonly ComplexDataConvertationDataAccessorInterface $dataAccessor,
        private readonly ComplexDataValueTransformerInterface $valueTransformer,
        private readonly string $entityType
    ) {
    }

    public function setConverterRegistry(ComplexDataConverterRegistry $converterRegistry): void
    {
        $this->converterRegistry = $converterRegistry;
    }

    #[\Override]
    public function process($item)
    {
        return $this->processEntity($item, $this->mappingProvider->getMapping(), $this->entityType);
    }

    private function processEntity(mixed $item, array $mapping, string $entityType): mixed
    {
        $entityMapping = $mapping[$entityType];
        if (!empty($entityMapping['fields'])) {
            $result = [];
            foreach ($entityMapping['fields'] as $fieldName => $fieldMapping) {
                if (\array_key_exists('value', $fieldMapping) || \array_key_exists('source', $fieldMapping)) {
                    continue;
                }

                $result[$fieldName] = $this->processField($fieldName, $fieldMapping, $item, $mapping);
            }
        } else {
            $result = $this->transformValue($this->dataAccessor->getLookupFieldValue(
                $item,
                $entityMapping['lookup_field'] ?? null,
                $entityMapping['entity'] ?? null
            ));
        }

        if (null !== $this->converterRegistry) {
            $converter = $this->converterRegistry->getReverseConverterForEntity($entityType);
            if (null !== $converter) {
                $result = $converter->reverseConvert($result, $item);
            }
        }

        return $result;
    }

    private function processField(string $fieldName, array $fieldMapping, mixed $item, array $mapping): mixed
    {
        if (empty($fieldMapping['target_path'])) {
            return null;
        }

        $fieldValue = $this->dataAccessor->getFieldValue($item, $fieldMapping['entity_path'] ?? $fieldName);
        if (!\array_key_exists('ref', $fieldMapping)) {
            return $this->transformValue(
                $fieldValue,
                $fieldMapping['entity_data_type'] ?? null
            );
        }
        if (null === $fieldValue) {
            return ($mapping[$fieldMapping['ref']]['collection'] ?? false)
                ? []
                : null;
        }
        if ($mapping[$fieldMapping['ref']]['collection'] ?? false) {
            $fieldValueResult = [];
            foreach ($fieldValue as $val) {
                $fieldValueResult[] = $this->processEntity($val, $mapping, $fieldMapping['ref']);
            }

            return $fieldValueResult;
        }
        if (\is_object($fieldValue) || \is_array($fieldValue)) {
            return $this->processEntity($fieldValue, $mapping, $fieldMapping['ref']);
        }

        return $this->transformValue(
            $fieldValue,
            $fieldMapping['entity_data_type'] ?? null
        );
    }

    private function transformValue(mixed $value, ?string $dataType = null): mixed
    {
        return $this->valueTransformer->transformValue($value, $dataType);
    }
}
