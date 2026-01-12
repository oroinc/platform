<?php

namespace Oro\Bundle\FilterBundle\Utils;

/**
 * Provides array utility methods for flattening nested arrays.
 *
 * This trait offers a reusable method for flattening multi-dimensional arrays into
 * a single-level associative array. It recursively processes nested arrays and
 * preserves the keys from the original structure, making it useful for simplifying
 * complex data structures in filter processing and data transformation operations.
 */
trait ArrayTrait
{
    /**
     * @param $array
     * @return array|bool
     */
    public function arrayFlatten($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = [];
        foreach ($array as $key => $value) {
            is_array($value) ?
                $result = array_merge($result, $this->arrayFlatten($value)) :
                $result[$key] = $value;
        }

        return $result;
    }
}
