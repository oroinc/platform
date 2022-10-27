<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Sorter;

/**
 * Represents a class that is used to sort filters for an API resource.
 */
interface FiltersSorterInterface
{
    /**
     * Sorts the given filters.
     *
     * @param array $filters [filter key => filter details, ...]
     *
     * @return array [filter key => filter details, ...] The sorted filters
     */
    public function sortFilters(array $filters): array;
}
