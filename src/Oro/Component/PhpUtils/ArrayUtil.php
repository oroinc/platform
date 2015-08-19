<?php

namespace Oro\Component\PhpUtils;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\PropertyAccess\PropertyAccessor;

class ArrayUtil
{
    /**
     * Checks whether the array is associative or sequential.
     *
     * @param array $array
     *
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        return array_values($array) !== $array;
    }

    /**
     * Sorts an array by specified property.
     *
     * This method uses the stable sorting algorithm. See http://en.wikipedia.org/wiki/Sorting_algorithm#Stability
     * Please use this method only if you really need stable sorting because this method is not so fast
     * as native PHP sort functions.
     *
     * @param array $array        The array to be sorted
     * @param bool  $reverse      Indicates whether the sorting should be performed
     *                            in reverse order
     * @param mixed $propertyPath The property accessor. Can be string or PropertyPathInterface or callable
     * @param int   $sortingFlags The sorting type. Can be SORT_NUMERIC or SORT_STRING
     *                            Also SORT_STRING can be combined with SORT_FLAG_CASE to sort
     *                            strings case-insensitively
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
        $caseInsensitive  = 0 !== ($sortingFlags & SORT_FLAG_CASE);

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
     * @param bool  $stringComparison
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
     * @param array                                 $array
     * @param string|PropertyPathInterface|callable $propertyPath
     * @param bool                                  $reverse
     * @param bool                                  $stringComparison
     * @param bool                                  $caseInsensitive
     *
     * @return array|null
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private static function prepareSortable($array, $propertyPath, $reverse, $stringComparison, $caseInsensitive)
    {
        $propertyAccessor     = new PropertyAccessor();
        $isSimplePropertyPath = is_string($propertyPath) && !preg_match('/.\[/', $propertyPath);
        $isCallback           = is_callable($propertyPath);
        $defaultValue         = $stringComparison ? '' : 0;
        $needSorting          = $reverse;

        $result  = [];
        $lastVal = null;
        $index   = 0;
        foreach ($array as $key => $value) {
            if (is_array($value) && $isSimplePropertyPath) {
                // get array property directly to speed up
                $val = isset($value[$propertyPath]) || array_key_exists($propertyPath, $value)
                    ? $value[$propertyPath]
                    : null;
            } elseif ($isCallback) {
                $val = call_user_func($propertyPath, $value);
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
                $lastVal     = $val;
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
     * @param bool  $stringComparison
     * @param bool  $reverse
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

    /**
     * Compares 2 values based on order specified in the argument
     *
     * @param int[] $order
     *
     * @return int
     */
    public static function createOrderedComparator(array $order)
    {
        return function ($a, $b) use ($order) {
            if (!array_key_exists($b, $order)) {
                return -1;
            }

            if (!array_key_exists($a, $order)) {
                return 1;
            }

            return $order[$a] - $order[$b];
        };
    }
}
