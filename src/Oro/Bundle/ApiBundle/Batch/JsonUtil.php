<?php

namespace Oro\Bundle\ApiBundle\Batch;

/**
 * The utility class that helps to convert an array to a JSON string and vise versa.
 */
class JsonUtil
{
    /**
     * @throws \JsonException if an error occurs
     */
    public static function encode(array $data, bool $prettyPrint = false): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | ($prettyPrint ? JSON_PRETTY_PRINT : 0));
    }

    /**
     * @throws \JsonException if an error occurs
     */
    public static function decode(string $str): array
    {
        return json_decode($str, true, 512, JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
    }
}
