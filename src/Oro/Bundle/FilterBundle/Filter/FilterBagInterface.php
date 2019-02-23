<?php

namespace Oro\Bundle\FilterBundle\Filter;

/**
 * Represents the container for filters.
 */
interface FilterBagInterface
{
    /**
     * Gets names of all registered filters.
     *
     * @return string[]
     */
    public function getFilterNames(): array;

    /**
     * Checks if a filter exists in this bag.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasFilter(string $name): bool;

    /**
     * Gets a filter by its name.
     *
     * @param string $name
     *
     * @return FilterInterface
     */
    public function getFilter(string $name): FilterInterface;
}
