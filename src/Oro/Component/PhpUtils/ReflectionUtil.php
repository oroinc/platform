<?php

namespace Oro\Component\PhpUtils;

class ReflectionUtil
{
    /**
     * Finds a property in a given class or any of its superclasses.
     *
     * @param \ReflectionClass $reflClass
     * @param string           $propertyName
     *
     * @return \ReflectionProperty|null
     */
    public static function getProperty(\ReflectionClass $reflClass, $propertyName)
    {
        $property = null;
        while ($reflClass) {
            if ($reflClass->hasProperty($propertyName)) {
                $property = $reflClass->getProperty($propertyName);
                break;
            }
            $reflClass = $reflClass->getParentClass();
        }

        return $property;
    }
}
