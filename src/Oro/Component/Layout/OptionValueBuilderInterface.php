<?php

namespace Oro\Component\Layout;

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
