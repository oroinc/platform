<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Component\PhpUtils\ArrayUtil;

class SortByExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_sort_by', [$this, 'sortBy'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_sort_by';
    }

    /**
     * Sorts an array by specified property.
     *
     * This method uses the stable sorting algorithm. See http://en.wikipedia.org/wiki/Sorting_algorithm#Stability
     *
     * Supported options:
     *  property     [string]  The path of the property by which the array should be sorted. Defaults to 'priority'
     *  reverse      [boolean] Indicates whether the sorting should be performed in reverse order. Defaults to FALSE
     *  sorting-type [string]  number, string or string-case (for case-insensitive sorting). Defaults to 'number'
     *
     * @param array $array   The array to be sorted
     * @param array $options The sorting options
     *
     * @return array The sorted array
     */
    public function sortBy(array $array, array $options = [])
    {
        $sortingType = self::getOption($options, 'sorting-type', 'number');
        if ($sortingType === 'number') {
            $sortingFlags = SORT_NUMERIC;
        } else {
            $sortingFlags = SORT_STRING;
            if ($sortingType === 'string-case') {
                $sortingFlags |= SORT_FLAG_CASE;
            }

        }

        ArrayUtil::sortBy(
            $array,
            self::getOption($options, 'reverse', false),
            self::getOption($options, 'property', 'priority'),
            $sortingFlags
        );

        return $array;
    }

    /**
     * @param array  $options
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    protected static function getOption($options, $name, $defaultValue = null)
    {
        return isset($options[$name])
            ? $options[$name]
            : $defaultValue;
    }
}
