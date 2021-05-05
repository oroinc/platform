<?php

namespace Oro\Component\Testing;

use Oro\Component\PhpUtils\ReflectionUtil as PhpReflectionUtil;

/**
 * Provides utility static methods to help working with reflection in tests.
 */
class ReflectionUtil
{
    /**
     * Sets the ID value to the given entity.
     *
     * @param object $entity
     * @param mixed  $id
     */
    public static function setId(object $entity, $id): void
    {
        self::setPropertyValue($entity, 'id', $id);
    }

    /**
     * Sets the given value to the given protected/private property.
     *
     * @param object $object
     * @param string $propertyName
     * @param mixed  $propertyValue
     */
    public static function setPropertyValue(object $object, string $propertyName, $propertyValue): void
    {
        $property = self::getProperty(new \ReflectionClass($object), $propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $propertyValue);
    }

    /**
     * Gets a value of the given protected/private property.
     *
     * @param object $object
     * @param string $propertyName
     *
     * @return mixed
     */
    public static function getPropertyValue(object $object, string $propertyName)
    {
        $property = self::getProperty(new \ReflectionClass($object), $propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Calls a protected/private method of the given object.
     *
     * @param object $object
     * @param string $methodName
     * @param array  $args
     *
     * @return mixed
     */
    public static function callMethod(object $object, string $methodName, array $args)
    {
        $reflClass = new \ReflectionClass($object);
        try {
            $method = $reflClass->getMethod($methodName);
        } catch (\ReflectionException $e) {
            throw new \LogicException(sprintf(
                'The method "%s" does not exist in the class "%s".',
                $methodName,
                $reflClass->getName()
            ), $e->getCode(), $e);
        }
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    /**
     * Finds a property in a given class or any of its superclasses.
     */
    private static function getProperty(\ReflectionClass $reflClass, string $propertyName): \ReflectionProperty
    {
        $property = PhpReflectionUtil::getProperty($reflClass, $propertyName);
        if (null === $property) {
            throw new \LogicException(sprintf(
                'The property "%s" does not exist in the class "%s".',
                $propertyName,
                $reflClass->getName()
            ));
        }

        return $property;
    }
}
