<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * This interface can be implemented by a filter that should search its own value by itself.
 * E.g. if a filter has dynamic filter key.
 */
interface SelfIdentifiableFilterInterface
{
    /**
     * Tries to find a value that is matched by this filter.
     *
     * @param FilterValue[] $filterValues [filter key => FilterValue, ...]
     *
     * @return string|null The key of found filter value
     *                     or NULL if the collection does not contain a value for this filter
     *
     * @throws InvalidFilterValueKeyException if a filter value was found, but its key is not valid by some reasons
     */
    public function searchFilterKey(array $filterValues);
}
