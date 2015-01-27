<?php

namespace Oro\Component\Layout;

interface ContextInterface
{
    /**
     * Checks whether the context contains the given value
     *
     * @param string $name The item name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Gets a value stored in the context
     *
     * @param string $name The item name
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException if a item does not exist
     */
    public function get($name);

    /**
     * Sets a value in the context
     *
     * @param string $name  The item name
     * @param mixed  $value The value to set
     *
     * @throws \BadMethodCallException always as setting a child by id is not allowed
     */
    public function set($name, $value);
}
