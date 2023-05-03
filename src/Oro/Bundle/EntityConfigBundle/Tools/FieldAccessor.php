<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;

/**
 * Can get and set object parameters based only on parameter names.
 */
class FieldAccessor
{
    /**
     * Gets the value of the field of the entity
     *
     * @param object $entity
     * @param string $fieldName
     * @return mixed
     */
    public static function getValue($entity, $fieldName)
    {
        return $entity->{'get' . InflectorFactory::create()->classify($fieldName)}();
    }

    /**
     * Sets the value of the field of the entity
     *
     * @param object $entity
     * @param string $fieldName
     * @param mixed  $value
     */
    public static function setValue($entity, $fieldName, $value)
    {
        $entity->{'set' . InflectorFactory::create()->classify($fieldName)}($value);
    }

    /**
     * Adds the related entity to the entity
     *
     * @param object $entity
     * @param string $fieldName
     * @param object $relatedEntity
     */
    public static function addValue($entity, $fieldName, $relatedEntity)
    {
        $entity->{'add' . InflectorFactory::create()->classify($fieldName)}($relatedEntity);
    }

    /**
     * Removes the related entity from the entity
     *
     * @param object $entity
     * @param string $fieldName
     * @param object $relatedEntity
     */
    public static function removeValue($entity, $fieldName, $relatedEntity)
    {
        $entity->{'remove' . InflectorFactory::create()->classify($fieldName)}($relatedEntity);
    }

    /**
     * Determines whether the getter for the field of the entity exists or not
     *
     * @param object $entity
     * @param string $fieldName
     * @return bool
     */
    public static function hasGetter($entity, $fieldName)
    {
        return EntityPropertyInfo::methodExists($entity, 'get' . InflectorFactory::create()->classify($fieldName));
    }
}
