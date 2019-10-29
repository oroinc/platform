<?php

namespace Oro\Bundle\EntityBundle\Helper;

/**
 * Manage unidirectional field definition
 *
 * \Acme\Bundle\Entity\SomeEntity
 *      * string - regular field
 *      * \Acme\Bundle\Entity\SomeEntity::reverseRelation - field defined in SomeEntity that points to SomeEntity
 */
class UnidirectionalFieldHelper
{
    const DELIMITER = '::';

    /**
     * @param $fieldName
     * @return bool
     */
    public static function isFieldUnidirectional($fieldName)
    {
        return 1 === substr_count($fieldName, self::DELIMITER);
    }

    /**
     * @param $fieldName
     * @return string
     */
    public static function getRealFieldName($fieldName)
    {
        $fieldChunks = explode(self::DELIMITER, $fieldName);
        if (count($fieldChunks) === 2) {
            return $fieldChunks[1];
        }

        return $fieldName;
    }

    /**
     * @param $fieldName
     * @return string
     */
    public static function getRealFieldClass($fieldName)
    {
        $fieldChunks = explode(self::DELIMITER, $fieldName);
        if (count($fieldChunks) === 2) {
            return $fieldChunks[0];
        }

        return null;
    }
}
