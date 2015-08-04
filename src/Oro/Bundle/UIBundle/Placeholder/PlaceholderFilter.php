<?php

namespace Oro\Bundle\UIBundle\Placeholder;

class PlaceholderFilter
{
    /**
     * Checks the object is an instance of a given class.
     *
     * @param object $obj
     * @param string $className
     * @return bool
     */
    public function isInstanceOf($obj, $className)
    {
        return $obj instanceof $className;
    }

    /**
     * Checks whether a given value equals TRUE
     *
     * @param mixed $val
     * @return bool
     */
    public function isTrue($val)
    {
        return (bool)$val;
    }
}
