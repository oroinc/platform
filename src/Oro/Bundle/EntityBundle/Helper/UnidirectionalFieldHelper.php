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
     * @param string $fieldName
     * @return bool
     */
    public static function isFieldUnidirectional(string $fieldName)
    {
        return 1 === substr_count($fieldName, self::DELIMITER);
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public static function getRealFieldName(string $fieldName)
    {
        $fieldChunks = explode(self::DELIMITER, $fieldName);
        if (count($fieldChunks) === 2) {
            return $fieldChunks[1];
        }

        return $fieldName;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public static function getRealFieldClass(string $fieldName)
    {
        $fieldChunks = explode(self::DELIMITER, $fieldName);
        if (count($fieldChunks) === 2) {
            return $fieldChunks[0];
        }

        return null;
    }
}
