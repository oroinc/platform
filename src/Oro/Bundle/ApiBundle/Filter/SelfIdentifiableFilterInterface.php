<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * This interface can be implemented by a filter that should search its own value by itself.
 * E.g. if a filter has dynamic filter key.
 */
interface SelfIdentifiableFilterInterface
{
    /**
     * Tries to find values that are matched by this filter.
     *
     * @param FilterValue[] $filterValues [filter key => FilterValue, ...]
     *
     * @return string[] The keys of found filter values
     *
     * @throws InvalidFilterValueKeyException if a filter value was found, but its key is not valid by some reasons
     */
    public function searchFilterKeys(array $filterValues): array;
}
