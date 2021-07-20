<?php

namespace Oro\Bundle\UIBundle\Placeholder;

/**
 * Provides some useful methods that can be used in placeholders.
 */
class PlaceholderFilter
{
    /**
     * Checks if two variables have the same type and value.
     *
     * @param mixed $val1
     * @param mixed $val2
     *
     * @return bool
     */
    public function isSame($val1, $val2)
    {
        return $val1 === $val2;
    }

    /**
     * Checks if an object is an instance of the given class.
     *
     * @param object|null $obj
     * @param string      $className
     *
     * @return bool
     */
    public function isInstanceOf($obj, $className)
    {
        return $obj instanceof $className;
    }

    /**
     * Checks if a string value equals to a given class or one of its parents.
     */
    public function isA(?string $val, string $className): bool
    {
        return $val && is_a($val, $className, true);
    }

    /**
     * Checks if a value equals to boolean TRUE value.
     *
     * @param mixed $val
     *
     * @return bool
     */
    public function isTrue($val)
    {
        return (bool)$val;
    }
}
