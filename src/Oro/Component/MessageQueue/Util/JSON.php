<?php

namespace Oro\Component\MessageQueue\Util;

/**
 * Contains handy methods for working with JSON.
 */
class JSON
{
    /**
     * @param mixed $data
     *
     * @param bool $throwOnError
     *
     * @return mixed
     *
     * @throws \JsonException
     */
    public static function decode(mixed $data, bool $throwOnError = true): mixed
    {
        if (!is_string($data)) {
            // Returns as is if data is already decoded.
            // BC layer for message queue processors that still decode message body on their own.
            return $data;
        }

        // Empty string and null cause syntax error when passed to json_decode().
        if ($data === '') {
            return null;
        }

        return json_decode($data, true, 512, $throwOnError ? JSON_THROW_ON_ERROR : 0);
    }

    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws \JsonException
     */
    public static function encode(mixed $value, bool $throwOnError = true): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | ($throwOnError ? JSON_THROW_ON_ERROR : 0));
    }
}
