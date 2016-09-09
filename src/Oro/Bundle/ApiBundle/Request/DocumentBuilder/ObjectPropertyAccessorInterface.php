<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

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
}
