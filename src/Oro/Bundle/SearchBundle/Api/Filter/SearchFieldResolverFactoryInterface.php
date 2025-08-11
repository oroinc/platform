<?php

namespace Oro\Bundle\SearchBundle\Api\Filter;

/**
 * Represents a factory to create {@see SearchFieldResolver}.
 */
interface SearchFieldResolverFactoryInterface
{
    /**
     * Creates a new instance of SearchFieldResolver.
     *
     * @param string $entityClass
     * @param array  $fieldMappings [field name => field name in search index, ...]
     *
     * @return SearchFieldResolver
     */
    public function createFieldResolver(string $entityClass, array $fieldMappings): SearchFieldResolver;
}
