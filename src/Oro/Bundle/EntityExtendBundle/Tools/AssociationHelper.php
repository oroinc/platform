<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Inflector\Inflector;

class AssociationHelper
{
    /**
     * Converts a string value into the format for a Doctrine class name. Converts 'table_name' to 'TableName'.
     *
     * @param string $value
     *
     * @return string
     */
    public static function classify($value)
    {
        return Inflector::classify(null === $value ? '' : $value);
    }

    /**
     * Get method method name to checks if an entity can be associated with another entity
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToOneSupportMethodName($associationType)
    {
        return sprintf('support%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to get associated entity
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToOneGetterMethodName($associationType)
    {
        return sprintf('get%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to set association to another entity
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToOneSetterMethodName($associationType)
    {
        return sprintf('set%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to reset associations
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToOneResetMethodName($associationType)
    {
        return sprintf('reset%sTargets', self::classify($associationType));
    }

    /**
     * Get method method name to get all associated entities
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToOneGetTargetEntitiesMethodName($associationType)
    {
        return sprintf('get%sTargetEntities', self::classify($associationType));
    }

    /**
     * Get method method name to checks if an entity can be associated with another entity
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getMultipleManyToOneSupportMethodName($associationType)
    {
        return sprintf('support%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to get associated entities
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getMultipleManyToOneGetterMethodName($associationType)
    {
        return sprintf('get%sTargets', self::classify($associationType));
    }

    /**
     * Get method method name to add association to another entity
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getMultipleManyToOneSetterMethodName($associationType)
    {
        return sprintf('add%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to reset associations
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getMultipleManyToOneResetMethodName($associationType)
    {
        return sprintf('reset%sTargets', self::classify($associationType));
    }

    /**
    * Get method method name to checks if an entity can be associated with another entity
    *
    * @param string|null $associationType The association type or NULL for unclassified (default) association
    *
    * @return string
    */
    public static function getManyToManySupportMethodName($associationType)
    {
        return sprintf('support%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to get associated entity
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToManyGetterMethodName($associationType)
    {
        return sprintf('get%sTargets', self::classify($associationType));
    }

    /**
     * Get method method name to check if entity is associated with another entity
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToManyHasMethodName($associationType)
    {
        return sprintf('has%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to set association to another entity
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToManySetterMethodName($associationType)
    {
        return sprintf('add%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to remove association
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToManyRemoveMethodName($associationType)
    {
        return sprintf('remove%sTarget', self::classify($associationType));
    }

    /**
     * Get method method name to get all associated entities
     *
     * @param string|null $associationType The association type or NULL for unclassified (default) association
     *
     * @return string
     */
    public static function getManyToManyGetTargetEntitiesMethodName($associationType)
    {
        return sprintf('get%sTargetEntities', self::classify($associationType));
    }
}
