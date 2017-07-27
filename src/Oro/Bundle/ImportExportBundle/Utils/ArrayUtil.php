<?php

namespace Oro\Bundle\ImportExportBundle\Utils;

class ArrayUtil
{
    /**
     * Remove all empty sub-arrays and leave first level empty arrays
     *
     * @param array   $data
     * @param integer $depth This variable reflects the depth of entering into an array. Please be careful with
     *                       this variable if you set $depth it will change current function logic. If you don`t know
     *                       do not set $depth
     *
     * @return array
     */
    public static function filterEmptyArrays(array $data, $depth = 0)
    {
        $hasValue = false;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value      = self::filterEmptyArrays($value, $depth + 1);
                $data[$key] = $value;
            }

            if ([] === $value && 0 !== $depth) {
                unset($data[$key]);
            } elseif (null !== $value) {
                $hasValue = true;
            }
        }

        return $hasValue ? $data : [];
    }
}
