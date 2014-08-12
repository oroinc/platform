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
     * @param string $fieldType
     * @param string $targetEntityClassName
     *
     * @return string
     */
    public static function buildRelationKey($entityClassName, $fieldName, $fieldType, $targetEntityClassName)
    {
        return implode('|', [$fieldType, $entityClassName, $targetEntityClassName, $fieldName]);
    }

    /**
     * Returns a string that can be used as a class name for the entity
     * represents values of the given enum.
     *
     * @param string $enumCode
     *
     * @return string
     */
    public static function buildEnumValueShortClassName($enumCode)
    {
        return 'EnumValue' . Inflector::classify($enumCode);
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
