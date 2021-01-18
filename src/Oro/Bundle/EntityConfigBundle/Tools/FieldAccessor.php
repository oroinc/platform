<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\Rules\English\InflectorFactory;

class FieldAccessor
{
    private static ?Inflector $inflector = null;

    /**
     * Gets the value of the field of the entity
     *
     * @param object $entity
     * @param string $fieldName
     * @return mixed
     */
    public static function getValue($entity, $fieldName)
    {
        return $entity->{'get' . self::getInflector()->classify($fieldName)}();
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
        $entity->{'set' . self::getInflector()->classify($fieldName)}($value);
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
        $entity->{'add' . self::getInflector()->classify($fieldName)}($relatedEntity);
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
        $entity->{'remove' . self::getInflector()->classify($fieldName)}($relatedEntity);
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
        return method_exists($entity, 'get' . self::getInflector()->classify($fieldName));
    }

    private static function getInflector(): Inflector
    {
        if (null === self::$inflector) {
            self::$inflector = (new InflectorFactory())->build();
        }
        return self::$inflector;
    }
}
