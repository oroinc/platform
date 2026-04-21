<?php

namespace Oro\Component\PhpUtils;

/**
 * Interface for PHP unserializer implementation.
 */
interface PhpUnserializerInterface
{
    public const WHITELIST_CLASSES_KEY = 'whitelisted_classes';

    /**
     * Unserialize a serialized string with a check for safe classes.
     * Helps to prevent unserialize() from being used in a malicious way.
     *
     * @param string $value The serialized string
     * @param array $options PHP Unserialize options plus an array with the following key:
     *                       - self::WHITELIST_CLASSES_KEY: an array of allowed classes
     * @return mixed
     */
    public function unserialize(string $value, array $options = []): mixed;

    /**
     * Checks if the given serialized string is safe to unserialize.
     */
    public function checkSerializedString(string $value, array $whitelistedClasses = []): void;
}
