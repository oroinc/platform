<?php

namespace Oro\Bundle\SearchBundle\Api;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Provides information about search mapping.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SearchMappingProvider
{
    private array $fieldMappings = [];

    public function __construct(
        private readonly AbstractSearchMappingProvider $searchMappingProvider,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
    }

    public function isSearchableEntity(string $entityClass): bool
    {
        return $this->searchMappingProvider->isClassSupported($entityClass);
    }

    /**
     * @return array [['name' => field name, 'type' => field type, 'entityFields' => [entity field name, ...]], ...]
     */
    public function getSearchFields(string $entityClass): array
    {
        $fields = [];
        $entityConfig = $this->searchMappingProvider->getEntityConfig($entityClass);
        if ($entityConfig) {
            $searchFieldMapping = array_flip($this->getFieldMappings($entityClass));
            $this->collectSearchEntityFields($fields, $entityConfig['fields'], $searchFieldMapping);
            $allTextEntityFieldNames = [];
            foreach ($fields as $field) {
                if (SearchQuery::TYPE_TEXT === $field['type']) {
                    $allTextEntityFieldNames[] = $field['entityFields'];
                }
            }
            $allTextEntityFieldNames = array_unique(array_merge(...$allTextEntityFieldNames));
            ksort($fields);
            $allTextFieldName = $this->resolveFieldName(SearchIndexer::TEXT_ALL_DATA_FIELD, $searchFieldMapping);
            $fields[$allTextFieldName] = [
                'name' => $allTextFieldName,
                'type' => SearchQuery::TYPE_TEXT,
                'entityFields' => $allTextEntityFieldNames,
            ];
        }

        return array_values($fields);
    }

    /**
     * @return array [field name => field type, ...]
     */
    public function getSearchFieldTypes(string $entityClass): array
    {
        $fieldTypes = [];
        $entityConfig = $this->searchMappingProvider->getEntityConfig($entityClass);
        if ($entityConfig) {
            $searchFieldMapping = array_flip($this->getFieldMappings($entityClass));
            $this->collectSearchFieldTypes($fieldTypes, $entityConfig['fields'], $searchFieldMapping);
            $allTextFieldName = $this->resolveFieldName(SearchIndexer::TEXT_ALL_DATA_FIELD, $searchFieldMapping);
            $fieldTypes[$allTextFieldName] = SearchQuery::TYPE_TEXT;
        }

        return $fieldTypes;
    }

    /**
     * @return array [field name => field name in search index, ...]
     */
    public function getFieldMappings(string $entityClass): array
    {
        if (isset($this->fieldMappings[$entityClass])) {
            return $this->fieldMappings[$entityClass];
        }

        $fieldMappings = [];
        $entityConfig = $this->searchMappingProvider->getEntityConfig($entityClass);
        if ($entityConfig && $this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $searchFieldMapping = [];
            $allTextExists = false;
            $this->collectSearchFieldMappings($searchFieldMapping, $allTextExists, $entityConfig['fields']);
            if ($allTextExists) {
                $allTextFieldName = $this->getAllTextFieldName();
                if (SearchIndexer::TEXT_ALL_DATA_FIELD !== $allTextFieldName) {
                    $fieldMappings[$allTextFieldName] = SearchIndexer::TEXT_ALL_DATA_FIELD;
                }
            }
            /** @var ClassMetadata $entityMetadata */
            $entityMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
            $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($entityClass);
            foreach ($searchFieldMapping as $searchFieldName => $entityFieldNames) {
                $guessedFieldName = $this->guessFieldName(
                    $searchFieldName,
                    $entityFieldNames,
                    $entityMetadata,
                    $ownershipMetadata
                );
                if ($guessedFieldName) {
                    $fieldMappings[$guessedFieldName] = $searchFieldName;
                }
            }
        }
        $this->fieldMappings[$entityClass] = $fieldMappings;

        return $fieldMappings;
    }

    public function getAllTextFieldName(): string
    {
        return $this->normalizeFieldName(SearchIndexer::TEXT_ALL_DATA_FIELD);
    }

    private function resolveFieldName(string $searchFieldName, array $searchFieldMapping): string
    {
        return $searchFieldMapping[$searchFieldName] ?? $searchFieldName;
    }

    private function normalizeFieldName(string $fieldName): string
    {
        return lcfirst(str_replace('_', '', ucwords($fieldName, ' _-')));
    }

    private function collectSearchEntityFields(
        array &$fields,
        array $fieldConfigs,
        array $searchFieldMapping,
        ?string $parentEntityFieldName = null
    ): void {
        foreach ($fieldConfigs as $fieldConfig) {
            $entityFieldName = $fieldConfig['name'];
            if ($parentEntityFieldName) {
                $entityFieldName = $parentEntityFieldName . '.' . $entityFieldName;
            }
            foreach ($fieldConfig['target_fields'] as $targetFieldName) {
                $fieldName = $this->resolveFieldName($targetFieldName, $searchFieldMapping);
                if (!isset($fields[$fieldName])) {
                    $fields[$fieldName] = [
                        'name' => $fieldName,
                        'type' => $fieldConfig['target_type'],
                        'entityFields' => [$entityFieldName],
                    ];
                } elseif (!\in_array($entityFieldName, $fields[$fieldName]['entityFields'], true)) {
                    $fields[$fieldName]['entityFields'][] = $entityFieldName;
                }
            }
            if (!empty($fieldConfig['relation_fields'])) {
                $this->collectSearchEntityFields(
                    $fields,
                    $fieldConfig['relation_fields'],
                    $searchFieldMapping,
                    $entityFieldName
                );
            }
        }
    }

    private function collectSearchFieldTypes(
        array &$fieldTypes,
        array $fieldConfigs,
        array $searchFieldMapping
    ): void {
        foreach ($fieldConfigs as $fieldConfig) {
            foreach ($fieldConfig['target_fields'] as $targetFieldName) {
                $fieldName = $this->resolveFieldName($targetFieldName, $searchFieldMapping);
                if (!isset($fieldTypes[$fieldName])) {
                    $fieldTypes[$fieldName] = $fieldConfig['target_type'];
                }
            }
            if (!empty($fieldConfig['relation_fields'])) {
                $this->collectSearchFieldTypes($fieldTypes, $fieldConfig['relation_fields'], $searchFieldMapping);
            }
        }
    }

    private function collectSearchFieldMappings(
        array &$searchFieldMapping,
        bool &$allTextExists,
        array $fieldConfigs,
        ?string $parentEntityFieldName = null
    ): void {
        foreach ($fieldConfigs as $fieldConfig) {
            $entityFieldName = $fieldConfig['name'];
            if ($parentEntityFieldName) {
                $entityFieldName = $parentEntityFieldName . '.' . $entityFieldName;
            }
            foreach ($fieldConfig['target_fields'] as $targetFieldName) {
                if (isset($searchFieldMapping[$targetFieldName])) {
                    $searchFieldMapping[$targetFieldName][] = $entityFieldName;
                } else {
                    $searchFieldMapping[$targetFieldName] = [$entityFieldName];
                    if (!$allTextExists && SearchQuery::TYPE_TEXT === $fieldConfig['target_type']) {
                        $allTextExists = true;
                    }
                }
            }
            if (!empty($fieldConfig['relation_fields'])) {
                $this->collectSearchFieldMappings(
                    $searchFieldMapping,
                    $allTextExists,
                    $fieldConfig['relation_fields'],
                    $entityFieldName
                );
            }
        }
    }

    private function guessFieldName(
        string $searchFieldName,
        array $entityFieldNames,
        ClassMetadata $entityMetadata,
        OwnershipMetadataInterface $ownershipMetadata
    ): ?string {
        if (\count($entityFieldNames) === 1) {
            $idFieldName = $this->guessIdFieldName($entityFieldNames[0], $entityMetadata);
            if ($idFieldName) {
                return $idFieldName;
            }
            $ownerFieldName = $this->guessOwnerFieldName($entityFieldNames[0], $entityMetadata, $ownershipMetadata);
            if ($ownerFieldName) {
                return $ownerFieldName;
            }
        }

        $fieldName = $this->normalizeFieldName($searchFieldName);
        if (!isset($searchFieldMapping[$fieldName])) {
            return $fieldName;
        }

        return null;
    }

    private function guessIdFieldName(string $entityFieldName, ClassMetadata $entityMetadata): ?string
    {
        $idFieldNames = $entityMetadata->getIdentifierFieldNames();
        if (\count($idFieldNames) === 1 && $idFieldNames[0] === $entityFieldName) {
            $fieldName = $this->normalizeFieldName($idFieldNames[0]);
            if (!isset($searchFieldMapping[$fieldName])) {
                return $fieldName;
            }
        }

        return null;
    }

    private function guessOwnerFieldName(
        string $entityFieldName,
        ClassMetadata $entityMetadata,
        OwnershipMetadataInterface $ownershipMetadata
    ): ?string {
        /** @noinspection NotOptimalIfConditionsInspection */
        if (($ownershipMetadata->isUserOwned() || $ownershipMetadata->isBusinessUnitOwned())
            && $ownershipMetadata->getOwnerFieldName() === $entityFieldName
        ) {
            $fieldName = $this->normalizeFieldName(
                $this->getShortClassName($entityMetadata->getAssociationTargetClass($entityFieldName))
            );
            if (!isset($searchFieldMapping[$fieldName])) {
                return $fieldName;
            }
        }

        return null;
    }

    private function getShortClassName(string $className): string
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }
}
