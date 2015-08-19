<?php

namespace Oro\Bundle\SoapBundle\Serializer;

interface DataAccessorInterface
{
    /**
     * Checks if a class has a getter for the given property
     *
     * @param string $className The FQCN
     * @param string $property  The name of the property
     *
     * @return boolean
     */
    public function hasGetter($className, $property);

    /**
     * Attempts to get the value of the specified property
     *
     * @param mixed  $object   The source object, can be an object or an array
     * @param string $property The name of the property
     * @param mixed  $value    Contains a value of the specified property;
     *                         if the operation failed a value of this variable is unpredictable
     *
     * @return boolean true if a value is got; otherwise, false
     */
    public function tryGetValue($object, $property, &$value);

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
    public function getValue($object, $property);
}
