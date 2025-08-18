<?php

namespace Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch;

use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;

/**
 * Provides the search mapping information for the search API resource.
 */
class MultiTargetSearchMappingProvider
{
    public function __construct(
        private readonly SearchMappingProvider $searchMappingProvider
    ) {
    }

    /**
     * @return array [field name in search index => ['type' => field type], ...]
     */
    public function getSearchFieldMappings(array $entityClasses, array $fieldMappings): array
    {
        $searchFieldMappings = [];
        foreach ($entityClasses as $entityClass) {
            $entityFieldTypes = $this->searchMappingProvider->getSearchFieldTypes($entityClass);
            foreach ($entityFieldTypes as $fieldName => $fieldType) {
                if (isset($fieldMappings[$fieldName])) {
                    foreach ((array)$fieldMappings[$fieldName] as $searchFieldName) {
                        if (!isset($searchFieldMappings[$searchFieldName])) {
                            $searchFieldMappings[$searchFieldName] = ['type' => $fieldType];
                        }
                    }
                } elseif (!isset($searchFieldMappings[$fieldName])) {
                    $searchFieldMappings[$fieldName] = ['type' => $fieldType];
                }
            }
        }

        return $searchFieldMappings;
    }

    /**
     * @return array [field name => field name in search index or [field name in search index, ...], ...]
     */
    public function getFieldMappings(array $entityClasses): array
    {
        $fieldMappings = [];
        foreach ($entityClasses as $entityClass) {
            $entityFieldMappings = $this->searchMappingProvider->getFieldMappings($entityClass);
            foreach ($entityFieldMappings as $fieldName => $searchFieldName) {
                if (!isset($fieldMappings[$fieldName])) {
                    $fieldMappings[$fieldName] = $searchFieldName;
                } elseif (\is_array($fieldMappings[$fieldName])) {
                    if (!\in_array($searchFieldName, $fieldMappings[$fieldName], true)) {
                        $fieldMappings[$fieldName][] = $searchFieldName;
                    }
                } elseif ($searchFieldName !== $fieldMappings[$fieldName]) {
                    $fieldMappings[$fieldName] = [$fieldMappings[$fieldName], $searchFieldName];
                }
            }
        }

        return $fieldMappings;
    }
}
