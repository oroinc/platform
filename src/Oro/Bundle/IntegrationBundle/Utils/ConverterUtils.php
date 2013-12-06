<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

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

        return array_map(['ConverterUtils', 'objectToArray'], $object);
    }
}
