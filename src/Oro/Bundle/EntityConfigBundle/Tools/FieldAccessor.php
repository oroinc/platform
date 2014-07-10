<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Common\Inflector\Inflector;

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
        return $entity->{'get' . Inflector::classify($fieldName)}();
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
        $entity->{'set' . Inflector::classify($fieldName)}($value);
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
        $entity->{'add' . Inflector::classify($fieldName)}($relatedEntity);
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
        $entity->{'remove' . Inflector::classify($fieldName)}($relatedEntity);
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
        return method_exists($entity, 'get' . Inflector::classify($fieldName));
    }
}
