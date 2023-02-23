<?php

namespace Oro\Component\ChainProcessor;

/**
 * Represents a container for key/value pairs.
 */
interface ParameterBagInterface extends \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * Checks whether a parameter with the given name exists in the bag.
     */
    public function has(string $key): bool;

    /**
     * Gets a parameter by name from the bag.
     * When a parameter does not exist the returned value is NULL.
     */
    public function get(string $key): mixed;

    /**
     * Sets a parameter by name into the bag.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Removes a parameter from the bag.
     */
    public function remove(string $key): void;

    /**
     * Gets a native PHP array representation of the bag.
     *
     * @return array [key => value, ...]
     */
    public function toArray(): array;

    /**
     * Removes all parameters from the bag.
     */
    public function clear(): void;
}
