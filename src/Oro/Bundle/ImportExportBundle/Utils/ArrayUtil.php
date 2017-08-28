<?php

namespace Oro\Bundle\ImportExportBundle\Utils;

class ArrayUtil
{
    /**
     * Remove all empty arrays and arrays with only null values
     *
     * @param array   $data
     *
     * @return array
     */
    public static function filterEmptyArrays(array $data)
    {
        $hasValue = false;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::filterEmptyArrays($value);
                $data[$key] = $value;
            }

            if (array() === $value) {
                unset($data[$key]);
            } elseif (null !== $value) {
                $hasValue = true;
            }
        }

        return $hasValue ? $data : array();
    }
}
