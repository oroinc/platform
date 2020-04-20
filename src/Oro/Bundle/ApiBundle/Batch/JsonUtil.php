<?php

namespace Oro\Bundle\ApiBundle\Batch;

/**
 * The utility class that helps to convert an array to a JSON sting and vise versa.
 */
class JsonUtil
{
    /**
     * @param array $data
     * @param bool  $prettyPrint
     *
     * @return string
     *
     * @throws \JsonException if an error occurs
     */
    public static function encode(array $data, bool $prettyPrint = false): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | ($prettyPrint ? JSON_PRETTY_PRINT : 0));
    }

    /**
     * @param string $str
     *
     * @return array
     *
     * @throws \JsonException if an error occurs
     */
    public static function decode(string $str): array
    {
        return json_decode($str, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
    }
}
