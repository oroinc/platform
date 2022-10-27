<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

/**
 * Provides an extension point for the search expression parser to be able to implement
 * custom mapping between field names and types in a search expression and search index.
 * @see \Oro\Bundle\SearchBundle\Query\Expression\Parser
 */
interface FieldResolverInterface
{
    /**
     * Returns the name that should be used in search index for the given field.
     */
    public function resolveFieldName(string $fieldName): string;

    /**
     * Returns the data-type of the given field.
     */
    public function resolveFieldType(string $fieldName): string;
}
