<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationDataAccessorInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping\ComplexDataMappingProvider;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Transforms data from plain array input format to JSON:API format.
 */
class JsonApiImportConverter
{
    private ?ComplexDataConverterRegistry $converterRegistry = null;

    public function __construct(
        private readonly ComplexDataMappingProvider $mappingProvider,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly ComplexDataConvertationDataAccessorInterface $dataAccessor,
        private readonly string $entityType
    ) {
    }

    public function setConverterRegistry(ComplexDataConverterRegistry $converterRegistry): void
    {
        $this->converterRegistry = $converterRegistry;
    }

    public function convert(array $item): array
    {
        $mapping = $this->mappingProvider->getMapping();
        $mappedData = $this->mapEntity($item, $mapping, $this->entityType);
        $result = [
            JsonApiDoc::DATA => array_merge(
                [JsonApiDoc::TYPE => $mapping[$this->entityType]['target_type']],
                $mappedData[ComplexDataConverterInterface::ENTITY]
            )
        ];
        if (!empty($mappedData[ComplexDataConverterInterface::INCLUDED])) {
            $result[JsonApiDoc::INCLUDED] = $mappedData[ComplexDataConverterInterface::INCLUDED];
        }
        if (!empty($mappedData[ComplexDataConverterInterface::ERRORS])) {
            foreach ($mappedData[ComplexDataConverterInterface::ERRORS] as $error) {
                $result[JsonApiDoc::ERRORS][] = $this->getJsonApiError(
                    $error[ComplexDataConverterInterface::ERROR_MESSAGE],
                    $error[ComplexDataConverterInterface::ERROR_PATH] ?? null
                );
            }
        }

        return $result;
    }

    private function mapEntity(mixed $data, array $mapping, string $refName): array
    {
        if (!\array_key_exists($refName, $mapping)) {
            throw new \LogicException(\sprintf('There is no mapping config for ref \'%s\'.', $refName));
        }

        $entityMapping = $mapping[$refName];
        if ($entityMapping['collection'] ?? false) {
            return $this->mapCollection($data, $mapping, $refName);
        }

        if (!\array_key_exists('entity', $entityMapping) && empty($entityMapping['fields'])) {
            return $this->processResult(
                $refName,
                $this->getResultItem($entityMapping['target_type'], $data),
                $data
            );
        }

        $entityClass = $entityMapping['entity'] ?? null;
        if ($entityClass) {
            $entityId = $this->dataAccessor->findEntityId($entityClass, $entityMapping['lookup_field'] ?? null, $data);
            if (null === $entityId) {
                if (!($entityMapping['ignore_not_found'] ?? false)) {
                    return [
                        ComplexDataConverterInterface::ERRORS => [
                            [ComplexDataConverterInterface::ERROR_MESSAGE => 'The entity was not found.']
                        ]
                    ];
                }

                return [];
            }

            return $this->processResult(
                $refName,
                $this->getResultItem($entityMapping['target_type'], $entityId),
                $data
            );
        }

        return $this->processResult(
            $refName,
            $this->mapStructuredEntity($data, $mapping, $entityMapping),
            $data
        );
    }

    private function mapCollection(array $data, array $mapping, string $refName): array
    {
        $mapping[$refName]['collection'] = false;
        $result = [ComplexDataConverterInterface::ENTITY => [], ComplexDataConverterInterface::INCLUDED => []];
        foreach ($data as $item) {
            $mappedData = $this->mapEntity($item, $mapping, $refName);
            $value = $mappedData[ComplexDataConverterInterface::ENTITY];
            if (\array_key_exists('target_type', $mappedData)) {
                $objectId = $this->getJsonApiObject($mappedData['target_type'], uniqid('tmp_', true));
                $mappedData[ComplexDataConverterInterface::INCLUDED][] = array_merge($objectId, $value);
                $value = $objectId;
            }

            $result[ComplexDataConverterInterface::ENTITY][] = $value;
            $result[ComplexDataConverterInterface::INCLUDED] = array_merge(
                $result[ComplexDataConverterInterface::INCLUDED],
                $mappedData[ComplexDataConverterInterface::INCLUDED]
            );
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function mapStructuredEntity(mixed $data, array $mapping, array $entityMapping): array
    {
        $result = [
            ComplexDataConverterInterface::ENTITY => [],
            ComplexDataConverterInterface::INCLUDED => [],
            ComplexDataConverterInterface::TARGET_TYPE => $entityMapping['target_type']
        ];
        $fields = $entityMapping['fields'] ?? [];
        foreach ($fields as $fieldName => $fieldMapping) {
            if (\array_key_exists('value', $fieldMapping)) {
                $value = $fieldMapping['value'];
            } elseif (\array_key_exists($fieldName, $data)) {
                $value = $data[$fieldName];
            } elseif (!empty($fieldMapping['source'])) {
                $value = $this->propertyAccessor->getValue($data, $this->buildPath($fieldMapping['source']));
            } else {
                continue;
            }

            $targetPath = $fieldMapping['target_path'] ?? null;
            if (!empty($fieldMapping['ref'])) {
                $mappedData = $this->mapEntity($value, $mapping, $fieldMapping['ref']);
                if (empty($mappedData)) {
                    continue;
                }
                $value = $mappedData[ComplexDataConverterInterface::ENTITY] ?? null;
                if ($value && \array_key_exists(ComplexDataConverterInterface::TARGET_TYPE, $mappedData)) {
                    $objectId = $this->getJsonApiObject(
                        $mappedData[ComplexDataConverterInterface::TARGET_TYPE],
                        uniqid('tmp_', true)
                    );
                    $mappedData[ComplexDataConverterInterface::INCLUDED][] = array_merge($objectId, $value);
                    $value = $objectId;
                }
                if (!empty($mappedData[ComplexDataConverterInterface::INCLUDED])) {
                    $result[ComplexDataConverterInterface::INCLUDED] = array_merge(
                        $result[ComplexDataConverterInterface::INCLUDED],
                        $mappedData[ComplexDataConverterInterface::INCLUDED]
                    );
                }
                if (!empty($mappedData[ComplexDataConverterInterface::ERRORS])) {
                    foreach ($mappedData[ComplexDataConverterInterface::ERRORS] as $error) {
                        $result[ComplexDataConverterInterface::ERRORS][] = [
                            ComplexDataConverterInterface::ERROR_MESSAGE =>
                                $error[ComplexDataConverterInterface::ERROR_MESSAGE],
                            ComplexDataConverterInterface::ERROR_PATH =>
                                $error[ComplexDataConverterInterface::ERROR_PATH] ?? $targetPath
                        ];
                    }
                }
            }

            if ($value && $targetPath) {
                $this->propertyAccessor->setValue(
                    $result[ComplexDataConverterInterface::ENTITY],
                    $this->buildPath($targetPath),
                    $value
                );
            }
        }

        return $result;
    }

    private function processResult(string $entityType, array $resultItem, mixed $sourceData): array
    {
        if (null !== $this->converterRegistry) {
            $converter = $this->converterRegistry->getConverterForEntity($entityType);
            if (null !== $converter) {
                $resultItem = $converter->convert($resultItem, $sourceData);
            }
        }

        return $resultItem;
    }

    private function getResultItem(string $objectType, string $objectId): array
    {
        return [
            ComplexDataConverterInterface::ENTITY => $this->getJsonApiObject($objectType, $objectId),
            ComplexDataConverterInterface::INCLUDED => []
        ];
    }

    private function getJsonApiObject(string $objectType, string $objectId): array
    {
        return [JsonApiDoc::TYPE => $objectType, JsonApiDoc::ID => $objectId];
    }

    private function getJsonApiError(string $message, ?string $path): array
    {
        $error = [
            JsonApiDoc::ERROR_TITLE => 'request data constraint',
            JsonApiDoc::ERROR_DETAIL => $message
        ];
        if ($path) {
            $error[JsonApiDoc::ERROR_SOURCE][JsonApiDoc::ERROR_POINTER] = \sprintf(
                '/%s/%s',
                JsonApiDoc::DATA,
                str_replace('.', '/', $path)
            );
        }

        return $error;
    }

    private function buildPath(string $targetPath): string
    {
        return '[' . str_replace('.', '][', $targetPath) . ']';
    }
}
