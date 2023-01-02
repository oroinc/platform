<?php

namespace Oro\Component\EntitySerializer;

/**
 * Represents a service to read property values from entity objects or arrays.
 */
interface DataAccessorInterface
{
    /**
     * Checks if a class has a getter for the given property
     *
     * @param string $className The FQCN
     * @param string $property  The name of the property
     *
     * @return bool
     */
    public function hasGetter(string $className, string $property): bool;

    /**
     * Attempts to get the value of the specified property
     *
     * @param mixed  $object   The source object, can be an object or an array
     * @param string $property The name of the property
     * @param mixed  $value    Contains a value of the specified property;
     *                         if the operation failed a value of this variable is unpredictable
     *
     * @return bool true if a value is got; otherwise, false
     */
    public function tryGetValue(object|array $object, string $property, mixed &$value): bool;

    /**
     * Returns the value of the specified property
     *
     * @param mixed  $object   The source object, can be an object or an array
     * @param string $property The name of the property
     *
     * @return mixed
     *
     * @throws \RuntimeException if the operation failed
     */
    public function getValue(object|array $object, string $property): mixed;
}
