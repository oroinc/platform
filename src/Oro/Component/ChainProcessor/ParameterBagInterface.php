<?php

namespace Oro\Component\ChainProcessor;

interface ParameterBagInterface extends \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * Checks whether a parameter with the given name exists in the bag.
     *
     * @param string $key The name of a parameter
     *
     * @return bool
     */
    public function has($key);

    /**
     * Gets a parameter by name from the bag.
     *
     * @param string $key The name of a parameter
     *
     * @return mixed|null The parameter value or NULL if it does not exist in the bag
     */
    public function get($key);

    /**
     * Sets a parameter by name into the bag.
     *
     * @param string $key   The name of a parameter
     * @param mixed  $value The value of a parameter
     */
    public function set($key, $value);

    /**
     * Removes a parameter from the bag.
     *
     * @param string $key The name of a parameter
     */
    public function remove($key);

    /**
     * Gets a native PHP array representation of the bag.
     *
     * @return array [key => value, ...]
     */
    public function toArray();

    /**
     * Removes all parameters from the bag.
     *
     * @return void
     */
    public function clear();
}
