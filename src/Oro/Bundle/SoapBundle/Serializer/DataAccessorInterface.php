<?php

namespace Oro\Bundle\SoapBundle\Serializer;

interface DataAccessorInterface
{
    /**
     * Checks if a class has a getter for the given property
     *
     * @param string $className
     * @param string $property
     *
     * @return boolean
     */
    public function hasGetter($className, $property);

    /**
     * Attempts to get the value of the specified field
     *
     * @param mixed  $object
     * @param string $property
     * @param mixed  $value    Contains a value of the specified property;
     *                         if the operation failed a value of this variable is unpredictable
     *
     * @return boolean true if a value is got; otherwise, false
     */
    public function tryGetValue($object, $property, &$value);

    /**
     * Returns the value of the specified field
     *
     * @param mixed  $object
     * @param string $property
     *
     * @return mixed
     *
     * @throws \RuntimeException if the operation failed
     */
    public function getValue($object, $property);
}
