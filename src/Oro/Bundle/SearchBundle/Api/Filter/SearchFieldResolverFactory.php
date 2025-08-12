<?php

namespace Oro\Bundle\SearchBundle\Api\Filter;

use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;

/**
 * The factory to create {@see SearchFieldResolver}.
 */
class SearchFieldResolverFactory implements SearchFieldResolverFactoryInterface
{
    public function __construct(
        private readonly SearchMappingProvider $searchMappingProvider
    ) {
    }

    #[\Override]
    public function createFieldResolver(string $entityClass, array $fieldMappings): SearchFieldResolver
    {
        $fieldMappings = array_merge($this->searchMappingProvider->getFieldMappings($entityClass), $fieldMappings);

        $searchFieldMappings = [];
        $sortableFieldTypes = $this->searchMappingProvider->getSearchFieldTypes($entityClass);
        foreach ($sortableFieldTypes as $fieldName => $fieldType) {
            $searchFieldName = $fieldMappings[$fieldName] ?? $fieldName;
            if (!isset($searchFieldMappings[$searchFieldName])) {
                $searchFieldMappings[$searchFieldName] = ['type' => $fieldType];
            }
        }

        return new SearchFieldResolver($searchFieldMappings, $fieldMappings);
    }
}
