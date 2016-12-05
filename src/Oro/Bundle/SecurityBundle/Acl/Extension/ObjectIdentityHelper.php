<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

class ObjectIdentityHelper
{
    const FIELD_DELIMITER = '::';

    const IDENTITY_TYPE_DELIMITER = ':';

    const GROUP_DELIMITER = '@';

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
        return $extensionKey . self::IDENTITY_TYPE_DELIMITER . $class;
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
     * Return true if given string is encoded identity
     * e.g. entity:Acme\Demo\Entity::some_field or action:some_action
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
     * Removes a field name from the given descriptor.
     *
     * @param string $val
     *
     * @return string
     */
    public static function removeFieldName($val)
    {
        $delim = strpos($val, self::FIELD_DELIMITER);
        if (false !== $delim) {
            $val = substr($val, 0, $delim);
        }

        return $val;
    }

    /**
     * Decode identity string to array with class and field names.
     *
     * @param string $val
     *
     * @return array [className, fieldName]
     */
    public static function decodeEntityFieldInfo($val)
    {
        return explode(self::FIELD_DELIMITER, $val, 2);
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
        return $entityClassName . self::FIELD_DELIMITER . $fieldName;
    }

    /**
     * Return true if given identity string contains class and field information.
     *
     * @param string $val
     *
     * @return bool
     */
    public static function isFieldEncodedKey($val)
    {
        return (bool)strpos($val, self::FIELD_DELIMITER);
    }

    /**
     * Builds a string that should be used as a type of an object identity.
     *
     * @param string      $type
     * @param string|null $group
     *
     * @return string
     */
    public static function buildType($type, $group = null)
    {
        return empty($group)
            ? $type
            : $group . self::GROUP_DELIMITER . $type;
    }

    /**
     * Removes a group identifier from the given value.
     *
     * @param string $type
     *
     * @return string
     */
    public static function removeGroupName($type)
    {
        $delim = strpos($type, self::GROUP_DELIMITER);
        if (false !== $delim) {
            $type = ltrim(substr($type, $delim + 1), ' ');
        }

        return $type;
    }

    /**
     * Extracts the normalized type and a group identifier from the given value.
     * Examples of types:
     *  Acme\DemoBundle\SomeEntity
     *  Acme\DemoBundle\SomeEntity@some_group
     *
     * @param string $type
     *
     * @return array [type, group]
     */
    public static function parseType($type)
    {
        $group = null;
        $delim = strpos($type, self::GROUP_DELIMITER);
        if (false !== $delim) {
            $group = strtolower(ltrim(substr($type, 0, $delim), ' '));
            $type = ltrim(substr($type, $delim + 1), ' ');
        }

        return [$type, $group];
    }
}
