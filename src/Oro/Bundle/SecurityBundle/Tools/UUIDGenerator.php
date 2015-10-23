<?php

namespace Oro\Bundle\SecurityBundle\Tools;

class UUIDGenerator
{
    /**
     * Generates most secure UUIDv4 identifier using openssl
     * extension if possible and will fallback to less secure mt_rand
     * solution if openssl is not installed
     *
     * @return string UUIDv4
     */
    public static function v4()
    {
        return extension_loaded('openssl') ? static::v4MostSecure() : static::v4LessSecure();
    }

    /**
     * Generates UUIDv4 using mt_rand function
     * which is not really secure
     *
     * @return string
     */
    protected static function v4LessSecure()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Generates secure UUIDv4 using openssl_random_pseudo_bytes function
     * which is considered most secure way of generating UUID
     *
     * @return string
     */
    protected static function v4MostSecure()
    {
        assert(extension_loaded('openssl'));

        $data = openssl_random_pseudo_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
