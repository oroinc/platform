<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

class ExtendHelper
{
    /**
     * @param $type
     * @return string
     */
    public static function getReversRelationType($type)
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
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $fieldType
     * @param string $targetEntityClassName
     * @return string
     */
    public static function buildRelationKey($entityClassName, $fieldName, $fieldType, $targetEntityClassName)
    {
        return implode('|', [$fieldType, $entityClassName, $targetEntityClassName, $fieldName]);
    }

    /**
     * Checks if an entity is a custom one
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @param string $className
     * @return bool
     */
    public static function isCustomEntity($className)
    {
        return strpos($className, ExtendConfigDumper::ENTITY) === 0;
    }
}
