<?php

namespace Oro\Component\Layout\Extension\Theme\Generator\Utils;

class ArrayUtils
{
    /**
     * Check is the array associative or sequential
     *
     * @param array $array
     *
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        return array_values($array) !== $array;
    }
}
