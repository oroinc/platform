<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

/**
 * An interface for classes that can provide an access to properties of a different object types.
 */
interface ObjectPropertyAccessorInterface
{
    /**
     * Returns the value of a given property.
     *
     * @param mixed  $object
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getValue($object, string $propertyName);

    /**
     * Checks whether the object has a given property.
     *
     * @param mixed  $object
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasProperty($object, string $propertyName): bool;
}
