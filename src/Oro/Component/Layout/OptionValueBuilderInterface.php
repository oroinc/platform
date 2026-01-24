<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for building block option values through add, remove, and replace operations.
 *
 * Implementations of this interface manage collections of values for block options, supporting
 * incremental construction through add, remove, and replace operations, with format-specific handling.
 */
interface OptionValueBuilderInterface
{
    /**
     * Requests to add new value
     *
     * @param mixed $value
     */
    public function add($value);

    /**
     * Requests to remove existing value
     *
     * @param mixed $value
     */
    public function remove($value);

    /**
     * Requests to replace one value with another value
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    public function replace($oldValue, $newValue);

    /**
     * Returns the built value for a block option
     *
     * @return mixed
     */
    public function get();
}
