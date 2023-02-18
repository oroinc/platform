<?php

namespace Oro\Bundle\SearchBundle\Api\Filter;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

/**
 * The factory to create SearchFieldResolver.
 */
class SearchFieldResolverFactory
{
    private AbstractSearchMappingProvider $searchMappingProvider;

    public function __construct(AbstractSearchMappingProvider $searchMappingProvider)
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    /**
     * Creates a new instance of SearchFieldResolver.
     *
     * @param string $entityClass
     * @param array  $fieldMappings [field name => field name in search index, ...]
     *
     * @return SearchFieldResolver
     */
    public function createFieldResolver(string $entityClass, array $fieldMappings): SearchFieldResolver
    {
        return new SearchFieldResolver(
            $this->getSearchFieldMappings($entityClass),
            $fieldMappings
        );
    }

    protected function getSearchFieldMappings(string $entityClass): array
    {
        $mapping = $this->searchMappingProvider->getEntityConfig($entityClass);

        return $mapping['fields'];
    }
}
