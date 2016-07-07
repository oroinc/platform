<?php
namespace Oro\Component\MessageQueue\Util;

class JSON
{
    /**
     * @param string $string
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public static function decode($string)
    {
        $decoded = json_decode($string, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'The malformed json given. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return $decoded;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public static function encode($value)
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'Could not encode value into json. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return $encoded;
    }
}
