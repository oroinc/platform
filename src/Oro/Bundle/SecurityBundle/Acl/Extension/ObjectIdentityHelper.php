<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

class ObjectIdentityHelper
{
    const FIELD_DELIMITER = '::';

    const IDENTITY_TYPE_DELIMITER = ':';

    /**
     * Parse identity string to array of values: id, type, and fieldName
     * Examples of string formats:
     *  entity:Acme\DemoBundle\SomeEntity::fieldName
     *  action:name_of_action
     *  entity:Acme\DemoBundle\SomeEntity
     *
     * @param string $val
     *
     * @return array [id, type, fieldName]
     */
    public static function parseIdentityString($val)
    {
        $type = $id = $fieldName = null;
        if (self::isEncodedIdentityString($val)) {
            $type = self::getClassFromIdentityString($val);
            $id = self::getExtensionKeyFromIdentityString($val);
            if (self::isFieldEncodedKey($type)) {
                list($type, $fieldName) = self::decodeEntityFieldInfo($type);
            }
        }

        return [$id, $type, $fieldName];
    }

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
        return strtolower(substr($identityString, 0, strpos($identityString, self::IDENTITY_TYPE_DELIMITER)));
    }

    /**
     * Return class from the identity string.
     *
     * @param string $identityString
     *
     * @return string
     */
    public static function getClassFromIdentityString($identityString)
    {
        return ltrim(substr($identityString, strpos($identityString, self::IDENTITY_TYPE_DELIMITER) + 1), ' ');
    }

    /**
     * Return true if given string is encoded identity (f.e. entity:\Acme\Demo\Entity::some_field or action:some_action)
     *
     * @param string $identityString
     *
     * @return bool
     */
    public static function isEncodedIdentityString($identityString)
    {
        return strpos($identityString, self::IDENTITY_TYPE_DELIMITER) > 0;
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
    public static function isFieldEncodedKey($key)
    {
        return (bool)strpos($key, self::FIELD_DELIMITER);
    }
}
