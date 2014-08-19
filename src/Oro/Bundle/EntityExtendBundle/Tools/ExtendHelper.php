<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Inflector\Inflector;

class ExtendHelper
{
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
     *
     * @return string
     */
    public static function buildEnumCode($enumName)
    {
        if (function_exists('iconv')) {
            $enumName = iconv('utf-8', 'ascii//TRANSLIT', $enumName);
        }

        return strtolower(
            preg_replace(
                ['/ +/', '/-+/', '/_{2,}/', '/[^a-z0-9_]+/i'],
                ['', '_', '_', ''],
                $enumName
            )
        );
    }

    /**
     * Returns full class name for an entity is used to store values of the given enum.
     *
     * @param string $enumCode
     *
     * @return string
     */
    public static function buildEnumValueClassName($enumCode)
    {
        return ExtendConfigDumper::ENTITY . 'EnumValue' . Inflector::classify($enumCode);
    }

    /**
     * Returns the name of a field that is used to store selected options for multiple enums
     * This field is required to avoid group by clause when multiple enum is shown in a datagrid
     *
     * @param string $fieldName The field name that is a reference to enum values table
     *
     * @return string
     */
    public static function getMultipleEnumSnapshotFieldName($fieldName)
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
