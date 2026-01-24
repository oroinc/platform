<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

/**
 * Provides utility methods for converting data structures.
 *
 * This utility class offers static methods for converting between different data types,
 * particularly for converting stdClass objects to arrays recursively. This is useful
 * when working with data from external APIs or JSON responses that need to be converted
 * to native PHP arrays for processing.
 */
class ConverterUtils
{
    /**
     * Convert stdClass to array recursively
     *
     * @param \stdClass $object
     *
     * @return array
     */
    public static function objectToArray($object)
    {
        if (!is_object($object) && !is_array($object)) {
            return $object;
        }

        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        return (array)array_map([__CLASS__, __METHOD__], $object);
    }
}
