<?php

namespace Oro\Bundle\UIBundle\Tools;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

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
     */
    public static function arrayMergeRecursiveDistinct(array $first, array $second)
    {
        foreach ($second as $idx => $value) {
            if (is_integer($idx)) {
                $first[] = $value;
            } else {
                if (!array_key_exists($idx, $first)) {
                    $first[$idx] = $value;
                } else {
                    if (is_array($value)) {
                        $first[$idx] = self::arrayMergeRecursiveDistinct($first[$idx], $value);
                    } else {
                        $first[$idx] = $value;
                    }
                }
            }
        }

        return $first;
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
     */
    public static function arrayColumn(array $array, $columnKey, $indexKey = null)
    {
        $result = [];

        if (empty($array)) {
            return [];
        }

        if (empty($columnKey)) {
            throw new \InvalidArgumentException('Column key is empty');
        }

        foreach ($array as $item) {
            if (!isset($item[$columnKey])) {
                continue;
            }

            if ($indexKey && !isset($item[$indexKey])) {
                continue;
            }

            if ($indexKey) {
                $index = $item[$indexKey];
                $result[$index] = $item[$columnKey];
            } else {
                $result[] = $item[$columnKey];
            }
        }

        return $result;
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
     */
    public static function sortBy(
        array &$array,
        $reverse = false,
        $propertyPath = 'priority',
        $sortingFlags = SORT_NUMERIC
    ) {
        if (empty($array)) {
            return;
        }

        /**
         * we have to implement such complex logic because the stable sorting is not supported in PHP for now
         * see https://bugs.php.net/bug.php?id=53341
         */

        $stringComparison = 0 !== ($sortingFlags & SORT_STRING);
        $caseInsensitive = 0 !== ($sortingFlags & SORT_FLAG_CASE);

        $sortable = self::prepareSortable($array, $propertyPath, $reverse, $stringComparison, $caseInsensitive);
        if (!empty($sortable)) {
            $keys = self::getSortedKeys($sortable, $stringComparison, $reverse);

            $result = [];
            foreach ($keys as $key) {
                if (is_string($key)) {
                    $result[$key] = $array[$key];
                } else {
                    $result[] = $array[$key];
                }
            }
            $array = $result;
        }
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param bool $stringComparison
     *
     * @return int
     */
    private static function compare($a, $b, $stringComparison = false)
    {
        if ($a === $b) {
            return 0;
        }

        if ($stringComparison) {
            return strcmp($a, $b);
        } else {
            return $a < $b ? -1 : 1;
        }
    }

    /**
     * @param array $array
     * @param string|PropertyPathInterface $propertyPath
     * @param bool $reverse
     * @param bool $stringComparison
     * @param bool $caseInsensitive
     *
     * @return array|null
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private static function prepareSortable($array, $propertyPath, $reverse, $stringComparison, $caseInsensitive)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $isSimplePropertyPath = is_string($propertyPath) && !preg_match('/.\[/', $propertyPath);
        $defaultValue = $stringComparison ? '' : 0;
        $needSorting = $reverse;

        $result = [];
        $lastVal = null;
        $index = 0;
        foreach ($array as $key => $value) {
            if (is_array($value) && $isSimplePropertyPath) {
                // get array property directly to speed up
                $val = isset($value[$propertyPath]) || array_key_exists($propertyPath, $value)
                    ? $value[$propertyPath]
                    : null;
            } else {
                $val = $propertyAccessor->getValue($value, $propertyPath);
            }
            if (null === $val) {
                $val = $defaultValue;
            } elseif ($caseInsensitive) {
                $val = strtolower($val);
            }

            $result[$key] = [$val, $index++];

            if ($lastVal === null) {
                $lastVal = $val;
            } elseif (0 !== self::compare($lastVal, $val, $stringComparison)) {
                $lastVal = $val;
                $needSorting = true;
            }
        }

        if (!$needSorting) {
            return null;
        }

        return $result;
    }

    /**
     * @param array $sortable
     * @param bool $stringComparison
     * @param bool $reverse
     *
     * @return array
     */
    private static function getSortedKeys($sortable, $stringComparison, $reverse)
    {
        uasort(
            $sortable,
            function ($a, $b) use ($stringComparison, $reverse) {
                $result = self::compare($a[0], $b[0], $stringComparison);
                if (0 === $result) {
                    $result = self::compare($a[1], $b[1]);
                } elseif ($reverse) {
                    $result = 0 - $result;
                }

                return $result;
            }
        );

        return array_keys($sortable);
    }
}
