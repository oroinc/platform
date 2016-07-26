<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;


class ObjectIdentityHelper
{
    const FIELD_DELIMITER = '::';

    const IDENTITY_TYPE_DELIMITER = ':';

    /**
     * Return full identity string by extension key and class name.
     *
     * @param string $extensionKey
     * @param string $class
     *
     * @return string
     */
    public static function encodeIdentityString($extensionKey, $class)
    {
        return sprintf('%s%s%s', $extensionKey, self::IDENTITY_TYPE_DELIMITER, $class);
    }

    /**
     * Return extension key from the identity string.
     *
     * @param string $identityString
     *
     * @return string
     */
    public static function getExtensionKeyFromIdentityString($identityString)
    {
        return substr($identityString, 0, strpos($identityString, self::IDENTITY_TYPE_DELIMITER));
    }

    /**
     * Return class from the identity string.
     *
     * @param $identityString
     *
     * @return string
     */
    public static function getClassFromIdentityString($identityString)
    {
        return substr($identityString, strpos($identityString, self::IDENTITY_TYPE_DELIMITER) +1);
    }

    /**
     * Decode identity string to array with class and field names.
     *
     * @param string $key
     *
     * @return array [className, fieldName]
     */
    public static function decodeEntityFieldInfo($key)
    {
        return explode(self::FIELD_DELIMITER, $key);
    }

    /**
     * Encode array with class and field names to identity string.
     *
     * @param string $entityClassName
     * @param string $fieldName
     *
     * @return string
     */
    public static function encodeEntityFieldInfo($entityClassName, $fieldName)
    {
        return sprintf('%s%s%s', $entityClassName, self::FIELD_DELIMITER, $fieldName);
    }

    /**
     * Return true if given identity string contains class and field information.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function isFieldDecodedKey($key)
    {
        return (bool)strpos($key, self::FIELD_DELIMITER);
    }
}
