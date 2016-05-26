<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Provides an interface of a collection of the FilterValue objects.
 */
interface FilterValueAccessorInterface
{
    /**
     * Checks whether a filter value with the given key exists.
     *
     * @param string $key The key of a filter value
     *
     * @return bool
     */
    public function has($key);

    /**
     * Gets a filter value.
     *
     * @param string $key The key of a filter value
     *
     * @return FilterValue|null The FilterValue or NULL if it was not found
     */
    public function get($key);

    /**
     * Gets all filters from the given group.
     *
     * @param string|null $group The name of a filter's group
     *
     * @return FilterValue[] [filter key => FilterValue, ...]
     */
    public function getGroup($group);

    /**
     * Gets all filters.
     *
     * @return FilterValue[] [filter key => FilterValue, ...]
     */
    public function getAll();

    /**
     * Sets a filter value.
     *
     * @param string           $key   The key of a filter value
     * @param FilterValue|null $value The filter value
     */
    public function set($key, FilterValue $value = null);

    /**
     * Removes a filter.
     *
     * @param string           $key   The key of a filter value
     */
    public function remove($key);
}
