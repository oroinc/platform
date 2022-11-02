<?php

namespace Oro\Component\PhpUtils;

/**
 * Provides utility static methods to get class information via the reflection.
 */
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
        if ($reflClass->hasProperty($propertyName)) {
            return $reflClass->getProperty($propertyName);
        }

        $reflClass = $reflClass->getParentClass();
        while ($reflClass) {
            if ($reflClass->hasProperty($propertyName)) {
                return $reflClass->getProperty($propertyName);
            }
            $reflClass = $reflClass->getParentClass();
        }

        return null;
    }
}
