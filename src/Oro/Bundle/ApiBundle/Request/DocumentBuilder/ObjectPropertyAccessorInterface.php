<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

/**
 * An interface for classes that can provide an access to properties of a different object types.
 */
interface ObjectPropertyAccessorInterface
{
    /**
     * Returns the value of a given property.
     */
    public function getValue(mixed $object, string $propertyName): mixed;

    /**
     * Checks whether the object has a given property.
     */
    public function hasProperty(mixed $object, string $propertyName): bool;
}
