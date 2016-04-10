<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

interface ObjectAccessorInterface
{
    /**
     * Returns FQCN of a given object.
     *
     * @param mixed $object
     *
     * @return string|null
     */
    public function getClassName($object);

    /**
     * Returns the value of a given property.
     *
     * @param mixed  $object
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getValue($object, $propertyName);

    /**
     * Checks whether the object has a given property.
     *
     * @param mixed  $object
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasProperty($object, $propertyName);

    /**
     * Returns an array contains all properties of a given object.
     *
     * @param mixed $object
     *
     * @return array
     */
    public function toArray($object);
}
