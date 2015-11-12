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
}
