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
}
