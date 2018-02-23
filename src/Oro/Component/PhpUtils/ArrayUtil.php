<?php

namespace Oro\Component\PhpUtils;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ArrayUtil
{
    /**
     * Returns elements of the array separated by separator
     *
     * @param mixed $separator
     * @param array $array
     *
     * @return array
     */
    public static function interpose($separator, array $array)
    {
        $result = [];
        foreach ($array as $element) {
            $result[] = $separator;
            $result[] = $element;
        }
        array_shift($result);

        return $result;
    }

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
     * @return callable
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

    /**
     * Return true if callback on any element returns truthy value, false otherwise
     *
     * @param callable $callback
     * @param array    $array
     *
     * @return boolean
     */
    public static function some(callable $callback, array $array)
    {
        foreach ($array as $item) {
            if (call_user_func($callback, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return first element on which callback returns true value, null otherwise
     *
     * @param callable $callback
     * @param array    $array
     *
     * @return mixed|null
     */
    public static function find(callable $callback, array $array)
    {
        foreach ($array as $item) {
            if (call_user_func($callback, $item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Return copy of the array starting with item for which callback returns falsity value
     *
     * @param callable $callback
     * @param array    $array
     *
     * @return array
     */
    public static function dropWhile(callable $callback, array $array)
    {
        foreach ($array as $key => $value) {
            if (!call_user_func($callback, $value)) {
                return array_slice($array, $key);
            }
        }

        return [];
    }

    /**
     * Recursively merge arrays.
     *
     * Merge two arrays as array_merge_recursive do, but instead of converting values to arrays when keys are same
     * replaces value from first array with value from second
     *
     * @param array $first
     * @param array $second
     *
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
                        if (is_array($first[$idx])) {
                            $first[$idx] = self::arrayMergeRecursiveDistinct($first[$idx], $value);
                        } else {
                            $first[$idx] = $value;
                        }
                    } else {
                        $first[$idx] = $value;
                    }
                }
            }
        }

        return $first;
    }

    /**
     * Return array of ranges (inclusive)
     * [[min1, max1], [min2, max2], ...]
     *
     * @param int[] $ints List of integers
     *
     * @return array
     */
    public static function intRanges(array $ints)
    {
        $ints = array_unique($ints);
        sort($ints);

        $result = [];
        while (false !== ($subResult = static::shiftRange($ints))) {
            $result[] = $subResult;
        }

        return $result;
    }

    /**
     * @param array $sortedUniqueInts
     *
     * @return array|false Array 2 elements [min, max] or false when the array is empty
     */
    public static function shiftRange(array &$sortedUniqueInts)
    {
        if (!$sortedUniqueInts) {
            return false;
        }

        $min = $max = reset($sortedUniqueInts);

        $c = 1;
        while (next($sortedUniqueInts) !== false && current($sortedUniqueInts) - $c === $min) {
            $max = current($sortedUniqueInts);
            array_shift($sortedUniqueInts);
            $c++;
        }
        array_shift($sortedUniqueInts);

        return [$min, $max];
    }

    /**
     * Return the values from a single column in the input array
     *
     * @link http://php.net/manual/en/function.array-column.php
     *
     * @param array $array
     * @param mixed $columnKey
     * @param mixed $indexKey
     *
     * @return array
     *
     * @deprecated since 1.10. Use native array_column instead
     */
    public static function arrayColumn(array $array, $columnKey, $indexKey = null)
    {
        $result = [];

        if (empty($array)) {
            return [];
        }

        if (!isset($columnKey)) {
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
                $index          = $item[$indexKey];
                $result[$index] = $item[$columnKey];
            } else {
                $result[] = $item[$columnKey];
            }
        }

        return $result;
    }

    /**
     * @param array $array
     * @param array $path
     *
     * @return array
     */
    public static function unsetPath(array $array, array $path)
    {
        $key = array_shift($path);

        if (!$path) {
            unset($array[$key]);

            return $array;
        }

        if (array_key_exists($key, $array) && is_array($array[$key])) {
            $array[$key] = static::unsetPath($array[$key], $path);
        }

        return $array;
    }

    /**
     * Returns the value in a nested associative array,
     * where $path is an array of keys. Returns $defaultValue if the key
     * is not present, or the not-found value if supplied.
     *
     * @param array $array
     * @param array $path
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public static function getIn(array $array, array $path, $defaultValue = null)
    {
        $propertyPath = implode(
            '',
            array_map(
                function ($part) {
                    return sprintf('[%s]', $part);
                },
                $path
            )
        );

        $propertyAccessor = new PropertyAccessor();
        if (!$propertyAccessor->isReadable($array, $propertyPath)) {
            return $defaultValue;
        }

        return $propertyAccessor->getValue($array, $propertyPath);
    }
}
