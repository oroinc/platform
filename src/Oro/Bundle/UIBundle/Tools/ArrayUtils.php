<?php

namespace Oro\Bundle\UIBundle\Tools;

use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @deprecated since 1.9. Use {@see Oro\Component\PhpUtils\ArrayUtil} instead
 */
class ArrayUtils
{
    /**
     * Recursively merge arrays.
     *
     * Merge two arrays as array_merge_recursive do, but instead of converting values to arrays when keys are same
     * replaces value from first array with value from second
     *
     * @param array $first
     * @param array $second
     * @return array
     *
     * @deprecated since 1.9. Use Oro\Component\PhpUtils\ArrayUtil::arrayMergeRecursiveDistinct instead
     */
    public static function arrayMergeRecursiveDistinct(array $first, array $second)
    {
        return ArrayUtil::arrayMergeRecursiveDistinct($first, $second);
    }

    /**
     * Return the values from a single column in the input array
     *
     * http://php.net/manual/en/function.array-column.php
     *
     * @param array $array
     * @param mixed $columnKey
     * @param mixed $indexKey
     *
     * @return array
     *
     * @deprecated since 1.9. Use Oro\Component\PhpUtils\ArrayUtil::arrayColumn instead
     */
    public static function arrayColumn(array $array, $columnKey, $indexKey = null)
    {
        return ArrayUtil::arrayColumn($array, $columnKey, $indexKey);
    }

    /**
     * Sorts an array by specified property.
     *
     * This method uses the stable sorting algorithm. See http://en.wikipedia.org/wiki/Sorting_algorithm#Stability
     * Please use this method only if you really need stable sorting because this method is not so fast
     * as native PHP sort functions.
     *
     * @param array $array The array to be sorted
     * @param bool $reverse Indicates whether the sorting should be performed
     *                                                   in reverse order
     * @param string|PropertyPathInterface $propertyPath The path of the property by which the array should be sorted
     * @param int $sortingFlags The sorting type. Can be SORT_NUMERIC or SORT_STRING
     *                                                   Also SORT_STRING can be combined with SORT_FLAG_CASE to sort
     *                                                   strings case-insensitively
     *
     * @deprecated since 1.9. Use Oro\Component\PhpUtils\ArrayUtil::sortBy instead
     */
    public static function sortBy(
        array &$array,
        $reverse = false,
        $propertyPath = 'priority',
        $sortingFlags = SORT_NUMERIC
    ) {
        ArrayUtil::sortBy($array, $reverse, $propertyPath, $sortingFlags);
    }
}
