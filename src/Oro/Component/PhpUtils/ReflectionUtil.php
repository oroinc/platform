<?php

namespace Oro\Component\PhpUtils;

/**
 * Provides utility static methods to get class information via the reflection.
 */
class ReflectionUtil
{
    /**
     * Finds a property in a given class or any of its superclasses.
     */
    public static function getProperty(\ReflectionClass $reflClass, string $propertyName): ?\ReflectionProperty
    {
        if ($reflClass->hasProperty($propertyName)) {
            return $reflClass->getProperty($propertyName);
        }

        $parentReflClass = $reflClass->getParentClass();
        while ($parentReflClass) {
            if ($parentReflClass->hasProperty($propertyName)) {
                return $parentReflClass->getProperty($propertyName);
            }
            $parentReflClass = $parentReflClass->getParentClass();
        }

        return null;
    }
}
