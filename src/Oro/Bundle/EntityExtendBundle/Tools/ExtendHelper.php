<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Inflector\Inflector;

class ExtendHelper
{
    const MAX_ENUM_VALUE_ID_LENGTH = 32;
    const MAX_ENUM_SNAPSHOT_LENGTH = 500;
    const BASE_ENUM_VALUE_CLASS    = 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue';

    /**
     * @param string $type
     *
     * @return string
     */
    public static function getReverseRelationType($type)
    {
        switch ($type) {
            case 'oneToMany':
                return 'manyToOne';
            case 'manyToOne':
                return 'oneToMany';
            case 'manyToMany':
                return 'manyToMany';
            default:
                return $type;
        }
    }

    /**
     * Returns a string that can be used as a field name to the relation to the given entity.
     *
     * To prevent name collisions this method adds a hash built based on the full class name
     * and the kind of the association to the end.
     *
     * @param string $targetEntityClassName The association target class name
     * @param string $associationKind       The kind of the association
     *                                      For example 'activity', 'sponsorship' etc
     *                                      NULL for unclassified (default) association
     *
     * @return string
     */
    public static function buildAssociationName($targetEntityClassName, $associationKind = null)
    {
        $hash = strtolower(
            dechex(
                crc32(
                    empty($associationKind) ? $targetEntityClassName : $associationKind . $targetEntityClassName
                )
            )
        );

        return sprintf(
            '%s_%s',
            Inflector::tableize(ExtendHelper::getShortClassName($targetEntityClassName)),
            $hash
        );
    }

    /**
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $relationType
     * @param string $targetEntityClassName
     *
     * @return string
     */
    public static function buildRelationKey($entityClassName, $fieldName, $relationType, $targetEntityClassName)
    {
        return implode('|', [$relationType, $entityClassName, $targetEntityClassName, $fieldName]);
    }

    /**
     * Returns an enum identifier based on the given enum name.
     *
     * @param string $enumName
     * @param bool   $throwExceptionIfInvalidName
     *
     * @return string The enum code. Can be empty string if $throwExceptionIfInvalidName = false
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumCode($enumName, $throwExceptionIfInvalidName = true)
    {
        if (empty($enumName)) {
            if (!$throwExceptionIfInvalidName) {
                return '';
            }

            throw new \InvalidArgumentException('$enumName must not be empty.');
        }

        if (function_exists('iconv')) {
            $enumName = iconv('utf-8', 'ascii//TRANSLIT', $enumName);
        }

        $result = strtolower(
            preg_replace(
                ['/ +/', '/-+/', '/[^a-z0-9_]+/i', '/_{2,}/'],
                ['_', '_', '', '_'],
                trim($enumName)
            )
        );
        if ($result === '_') {
            $result = '';
        }

        if (empty($result) && $throwExceptionIfInvalidName) {
            throw new \InvalidArgumentException(
                sprintf('The conversion of "%s" to enum code produces empty string.', $enumName)
            );
        }

        return $result;
    }

    /**
     * Generates an enum identifier based on the given entity class and field.
     * This method can be used if there is no enum name and as result
     * {@link buildEnumCode()} method cannot be used.
     *
     * @param string $entityClassName
     * @param string $fieldName
     *
     * @return string The enum code.
     *
     * @throws \InvalidArgumentException
     */
    public static function generateEnumCode($entityClassName, $fieldName)
    {
        if (empty($entityClassName)) {
            throw new \InvalidArgumentException('$entityClassName must not be empty.');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty.');
        }

        return sprintf(
            '%s_%s_%s',
            Inflector::tableize(self::getShortClassName($entityClassName)),
            Inflector::tableize($fieldName),
            dechex(crc32($entityClassName . '::' . $fieldName))
        );
    }

    /**
     * Returns an enum value identifier based on the given value name.
     *
     * @param string $enumValueName
     * @param bool   $throwExceptionIfInvalidName
     *
     * @return string The enum value identifier. Can be empty string if $throwExceptionIfInvalidName = false
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumValueId($enumValueName, $throwExceptionIfInvalidName = true)
    {
        if (empty($enumValueName)) {
            if (!$throwExceptionIfInvalidName) {
                return '';
            }

            throw new \InvalidArgumentException('$enumValueName must not be empty.');
        }

        if (function_exists('iconv')) {
            $enumValueName = iconv('utf-8', 'ascii//TRANSLIT', $enumValueName);
        }

        $result = strtolower(
            preg_replace(
                ['/ +/', '/-+/', '/[^a-z0-9_]+/i', '/_{2,}/'],
                ['_', '_', '', '_'],
                trim($enumValueName)
            )
        );
        if ($result === '_') {
            $result = '';
        }

        if (strlen($result) > self::MAX_ENUM_VALUE_ID_LENGTH) {
            $hash   = dechex(crc32($result));
            $result = substr($result, 0, self::MAX_ENUM_VALUE_ID_LENGTH - strlen($hash) - 1) . '_' . $hash;
        }

        if (empty($result) && $throwExceptionIfInvalidName) {
            throw new \InvalidArgumentException(
                sprintf('The conversion of "%s" to enum value id produces empty string.', $enumValueName)
            );
        }

        return $result;
    }

    /**
     * Returns full class name for an entity is used to store values of the given enum.
     *
     * @param string $enumCode
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function buildEnumValueClassName($enumCode)
    {
        if (empty($enumCode)) {
            throw new \InvalidArgumentException('$enumCode must not be empty.');
        }

        return ExtendConfigDumper::ENTITY . 'EV_' . str_replace(" ", "_", ucwords(strtr($enumCode, "_-", "  ")));
    }

    /**
     * Returns the name of a field that is used to store selected options for multiple enums
     * This field is required to avoid group by clause when multiple enum is shown in a datagrid
     *
     * @param string $fieldName The field name that is a reference to enum values table
     *
     * @return string
     */
    public static function getMultiEnumSnapshotFieldName($fieldName)
    {
        return $fieldName . 'Snapshot';
    }

    /**
     * Returns a translation key (placeholder) for entities responsible to store enum values
     *
     * @param string $propertyName property key: label, description, plural_label, etc.
     * @param string $enumCode
     * @param string $fieldName
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getEnumTranslationKey($propertyName, $enumCode, $fieldName = null)
    {
        if (empty($propertyName)) {
            throw new \InvalidArgumentException('$propertyName must not be empty');
        }
        if (empty($enumCode)) {
            throw new \InvalidArgumentException('$enumCode must not be empty');
        }

        if (!$fieldName) {
            return sprintf('oro.entityextend.enums.%s.entity_%s', $enumCode, $propertyName);
        }

        return sprintf('oro.entityextend.enumvalue.%s.%s', $fieldName, $propertyName);
    }

    /**
     * Checks if an entity is a custom one
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @param string $className
     *
     * @return bool
     */
    public static function isCustomEntity($className)
    {
        return strpos($className, ExtendConfigDumper::ENTITY) === 0;
    }

    /**
     * Gets the short name of the class, the part without the namespace.
     *
     * @param string $className The full name of a class
     *
     * @return string
     */
    public static function getShortClassName($className)
    {
        $lastDelimiter = strrpos($className, '\\');

        return false === $lastDelimiter
            ? $className
            : substr($className, $lastDelimiter + 1);
    }
}
