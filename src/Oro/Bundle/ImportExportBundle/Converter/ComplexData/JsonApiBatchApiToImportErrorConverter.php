<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping\ComplexDataMappingProvider;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Converts Batch API errors in JSON:API format to errors in import format.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class JsonApiBatchApiToImportErrorConverter implements BatchApiToImportErrorConverterInterface
{
    private ?ComplexDataConverterRegistry $converterRegistry = null;
    private ?array $mapping = null;
    private ?array $entityTypeMapping = null;

    public function __construct(
        private readonly ComplexDataMappingProvider $mappingProvider,
        private readonly TranslatorInterface $translator,
        private readonly string $entityType
    ) {
    }

    public function setConverterRegistry(ComplexDataConverterRegistry $converterRegistry): void
    {
        $this->converterRegistry = $converterRegistry;
    }

    #[\Override]
    public function convertToImportError(BatchError $error, array $requestData, ?int $rowIndex = null): string
    {
        $entityIndex = $error->getItemIndex();
        $prefix = $this->getErrorPrefix(($rowIndex ?? $entityIndex) + 1) . ' ';

        $propertyPath = null;
        $errorEntityType = null;
        $errorPropertyPath = null;
        $errorDetail = $error->getDetail();
        $suffix = '';
        $source = $error->getSource();
        if (null !== $source) {
            $propertySource = null;
            $sourcePointer = $source->getPointer();
            if (null !== $sourcePointer) {
                $path = explode('/', $sourcePointer);
                $propertyPath = $this->getErrorPropertyPath($entityIndex, $path, $requestData);
                if (null === $propertyPath) {
                    $propertySource = $sourcePointer;
                }
                [$errorEntityType, $errorPropertyPath] = $this->getErrorInfo($path, $requestData);
            } elseif (null !== $source->getPropertyPath()) {
                $propertySource = $source->getPropertyPath();
            } elseif (null !== $source->getParameter()) {
                $propertySource = $source->getParameter();
            }
            if ($propertyPath) {
                $prefix .= $propertyPath . ': ';
            }
            if ($propertySource) {
                $suffix = \sprintf(' Source: %s.', $propertySource);
            }
        }
        $errorDetail = $this->normalizeErrorDetail($errorDetail, $errorEntityType, $errorPropertyPath);

        return $prefix . $errorDetail . $suffix;
    }

    private function getErrorPrefix(int $rowNumber): string
    {
        return $this->translator->trans(
            'oro.importexport.import.error %number%',
            ['%number%' => $rowNumber]
        );
    }

    private function normalizeErrorDetail(?string $errorDetail, ?string $entityType, ?string $propertyPath): ?string
    {
        if (null !== $this->converterRegistry && $errorDetail) {
            $converter = $this->converterRegistry->getErrorConverterForEntity($entityType ?? $this->entityType);
            if (null !== $converter) {
                $errorDetail = $converter->convertError($errorDetail, $propertyPath);
            }
        }

        return $errorDetail;
    }

    private function getErrorInfo(array $path, array $requestData): array
    {
        $pathCount = \count($path);
        if ($pathCount >= 3) {
            if (JsonApiDoc::DATA === $path[1]) {
                // Examples: /data/1, /data/1/attributes/name, /data/1/relationships/name/data
                $propertyPath = null;
                if ($pathCount >= 5) {
                    $propertyPath = $this->getErrorPropertyPathBySourcePath($this->entityType, $path);
                }

                return [$this->entityType, $propertyPath];
            }
            if (JsonApiDoc::INCLUDED === $path[1]) {
                // Examples: /included/1, /included/1/attributes/name, /included/1/relationships/name/data
                $includedTargetType = $requestData[JsonApiDoc::INCLUDED][(int)$path[2]][JsonApiDoc::TYPE] ?? null;
                if ($includedTargetType) {
                    $includedEntityType = $this->getEntityTypeByTargetType($includedTargetType);
                    if ($includedEntityType) {
                        $propertyPath = null;
                        if ($pathCount >= 5) {
                            $propertyPath = $this->getErrorPropertyPathBySourcePath($includedEntityType, $path);
                        }

                        return [$includedEntityType, $propertyPath];
                    }
                }
            }
        }

        return [null, null];
    }

    private function getErrorPropertyPath(int $entityIndex, array $path, array $requestData): ?string
    {
        $pathCount = \count($path);
        if (3 === $pathCount) {
            // Examples: /data/1, /included/1
            return $this->getErrorPropertyPathForEntity($entityIndex, $path, $requestData);
        }
        if ($pathCount >= 5) {
            // Examples: /data/1/attributes/name, /data/1/relationships/name/data,
            //           /included/1/attributes/name, /included/1/relationships/name/data
            return $this->getErrorPropertyPathForEntityProperty($entityIndex, $path, $requestData);
        }

        return null;
    }

    private function getErrorPropertyPathForEntity(int $entityIndex, array $path, array $requestData): ?string
    {
        if (JsonApiDoc::DATA === $path[1]) {
            // Example: /data/1
            return '';
        }
        if (JsonApiDoc::INCLUDED === $path[1]) {
            // Example: /included/1
            return $this->findRelationshipPropertyPathForIncludedEntity(
                $requestData,
                (int)$path[2],
                $entityIndex
            );
        }

        return null;
    }

    private function getErrorPropertyPathForEntityProperty(int $entityIndex, array $path, array $requestData): ?string
    {
        if (JsonApiDoc::DATA === $path[1]) {
            // Examples: /data/1/attributes/name, /data/1/relationships/name/data
            return $this->getErrorPropertyPathBySourcePath($this->entityType, $path);
        }
        if (JsonApiDoc::INCLUDED === $path[1]) {
            if (JsonApiDoc::ATTRIBUTES === $path[3]) {
                // Example: /included/1/attributes/name
                return $this->getErrorPropertyPathForIncludedEntityAttribute(
                    $path[4],
                    $entityIndex,
                    (int)$path[2],
                    $requestData
                );
            }
            if (JsonApiDoc::RELATIONSHIPS === $path[3]) {
                // Example: /included/1/relationships/name/data
                return $this->getErrorPropertyPathForIncludedEntityRelationship(
                    $path[4],
                    $entityIndex,
                    (int)$path[2],
                    $requestData
                );
            }
        }

        return null;
    }

    private function getErrorPropertyPathForIncludedEntityAttribute(
        string $attributeName,
        int $entityIndex,
        int $includedIndex,
        array $requestData
    ): ?string {
        $relationshipPropertyPath = $this->findRelationshipPropertyPathForIncludedEntity(
            $requestData,
            $includedIndex,
            $entityIndex
        );
        if (!$relationshipPropertyPath) {
            return null;
        }

        $includedTargetType = $requestData[JsonApiDoc::INCLUDED][$includedIndex][JsonApiDoc::TYPE] ?? null;
        if ($includedTargetType) {
            $entityType = $this->getEntityTypeByTargetType($includedTargetType);
            if ($entityType) {
                $propertyPath = $this->getErrorPropertyPathByTargetPath(
                    $entityType,
                    $this->buildPath([JsonApiDoc::ATTRIBUTES, $attributeName])
                );
                if ($propertyPath) {
                    $attributeName = $propertyPath;
                }
            }
        }

        return $this->buildPath([$relationshipPropertyPath, $attributeName]);
    }

    private function getErrorPropertyPathForIncludedEntityRelationship(
        string $relationshipName,
        int $entityIndex,
        int $includedIndex,
        array $requestData
    ): ?string {
        $relationshipPropertyPath = $this->findRelationshipPropertyPathForIncludedEntity(
            $requestData,
            $includedIndex,
            $entityIndex
        );
        if (!$relationshipPropertyPath) {
            return null;
        }

        $includedTargetType = $requestData[JsonApiDoc::INCLUDED][$includedIndex][JsonApiDoc::TYPE] ?? null;
        if ($includedTargetType) {
            $entityType = $this->getEntityTypeByTargetType($includedTargetType);
            if ($entityType) {
                $propertyPath = $this->getErrorPropertyPathByTargetPath(
                    $entityType,
                    $this->buildPath([JsonApiDoc::RELATIONSHIPS, $relationshipName, JsonApiDoc::DATA])
                );
                if ($propertyPath) {
                    $relationshipName = $propertyPath;
                }
            }
        }

        return $this->buildPath([$relationshipPropertyPath, $relationshipName]);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function findRelationshipPropertyPathForIncludedEntity(
        array $requestData,
        int $includedIndex,
        int $entityIndex
    ): ?string {
        $includedTargetType = $requestData[JsonApiDoc::INCLUDED][$includedIndex][JsonApiDoc::TYPE] ?? null;
        if (!$includedTargetType) {
            return null;
        }
        $includedId = $requestData[JsonApiDoc::INCLUDED][$includedIndex][JsonApiDoc::ID] ?? null;
        if (!$includedId) {
            return null;
        }

        $relationshipPropertyPath = $this->findRelationshipPropertyPath(
            $this->entityType,
            $requestData[JsonApiDoc::DATA][$entityIndex][JsonApiDoc::RELATIONSHIPS] ?? [],
            $includedTargetType,
            $includedId
        );
        if (null === $relationshipPropertyPath) {
            foreach ($requestData[JsonApiDoc::INCLUDED] as $item) {
                $itemTargetType = $item[JsonApiDoc::TYPE] ?? null;
                if (!$itemTargetType) {
                    continue;
                }
                $itemId = $item[JsonApiDoc::ID] ?? null;
                if (!$itemId) {
                    continue;
                }
                if ($itemTargetType === $includedTargetType && $itemId === $includedId) {
                    continue;
                }
                $itemEntityType = $this->getEntityTypeByTargetType($itemTargetType);
                if (!$itemEntityType) {
                    continue;
                }
                $relationshipPropertyPath = $this->findRelationshipPropertyPath(
                    $itemEntityType,
                    $item[JsonApiDoc::RELATIONSHIPS] ?? [],
                    $includedTargetType,
                    $includedId
                );
                if (null !== $relationshipPropertyPath) {
                    $relationshipPropertyPath = $this->buildPath([$itemEntityType, $relationshipPropertyPath]);
                    break;
                }
            }
        }

        return $relationshipPropertyPath;
    }

    private function findRelationshipPropertyPath(
        string $entityType,
        array $relationships,
        string $relatedTargetType,
        string $relatedId
    ): ?string {
        foreach ($relationships as $relationshipName => $relationship) {
            $relationshipData = $relationship[JsonApiDoc::DATA] ?? [];
            if (!$relationshipData) {
                continue;
            }
            if (ArrayUtil::isAssoc($relationshipData)) {
                if (
                    ($relationshipData[JsonApiDoc::TYPE] ?? null) === $relatedTargetType
                    && ($relationshipData[JsonApiDoc::ID] ?? null) === $relatedId
                ) {
                    return $this->getErrorPropertyPathByTargetPath(
                        $entityType,
                        $this->buildPath([JsonApiDoc::RELATIONSHIPS, $relationshipName, JsonApiDoc::DATA])
                    ) ?? $relationshipName;
                }
            } else {
                foreach ($relationshipData as $itemIndex => $item) {
                    if (
                        ($item[JsonApiDoc::TYPE] ?? null) === $relatedTargetType
                        && ($item[JsonApiDoc::ID] ?? null) === $relatedId
                    ) {
                        return $this->buildPath([
                            $this->getErrorPropertyPathByTargetPath(
                                $entityType,
                                $this->buildPath([JsonApiDoc::RELATIONSHIPS, $relationshipName, JsonApiDoc::DATA])
                            ) ?? $relationshipName,
                            (string)$itemIndex
                        ]);
                    }
                }
            }
        }

        return null;
    }

    private function buildPath(array $path): string
    {
        return implode('.', $path);
    }

    private function getMapping(): array
    {
        if (null === $this->mapping) {
            $this->mapping = $this->mappingProvider->getMapping();
        }

        return $this->mapping;
    }

    private function getEntityTypeByTargetType(string $targetType): ?string
    {
        if (null === $this->entityTypeMapping) {
            $entityTypeMapping = [];
            $mapping = $this->getMapping();
            foreach ($mapping as $entityType => $entityConfig) {
                $entityTargetType = $entityConfig['target_type'] ?? null;
                if ($entityTargetType) {
                    $entityTypeMapping[$entityTargetType] = $entityType;
                }
            }
            $this->entityTypeMapping = $entityTypeMapping;
        }

        return $this->entityTypeMapping[$targetType] ?? null;
    }

    private function getErrorPropertyPathBySourcePath(string $entityType, array $sourcePath): ?string
    {
        if (JsonApiDoc::ATTRIBUTES === $sourcePath[3]) {
            // Examples: /data/1/attributes/name, /included/1/attributes/name
            return $this->getErrorPropertyPathByTargetPath(
                $entityType,
                $this->buildPath([$sourcePath[3], $sourcePath[4]])
            );
        }
        if (JsonApiDoc::RELATIONSHIPS === $sourcePath[3]) {
            // Examples: /data/1/relationships/name/data, /included/1/relationships/name/data
            return $this->getErrorPropertyPathByTargetPath(
                $entityType,
                $this->buildPath([$sourcePath[3], $sourcePath[4], $sourcePath[5]])
            );
        }

        return null;
    }

    private function getErrorPropertyPathByTargetPath(string $entityType, string $targetPath): ?string
    {
        $mapping = $this->getMapping();
        $entityMapping = $mapping[$entityType] ?? [];
        $fields = $entityMapping['fields'] ?? [];
        foreach ($fields as $fieldName => $fieldConfig) {
            if (!empty($fieldConfig['target_path']) && $fieldConfig['target_path'] === $targetPath) {
                $result = $fieldName;
                if (\array_key_exists('source', $fieldConfig)) {
                    $result = $fieldConfig['source'];
                }

                return $result;
            }
        }

        return null;
    }
}
