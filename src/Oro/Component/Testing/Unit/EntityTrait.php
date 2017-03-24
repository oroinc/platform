<?php

namespace Oro\Component\Testing\Unit;

use Oro\Component\Testing\Unit\PropertyAccess\PropertyAccessTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

trait EntityTrait
{
    use PropertyAccessTrait;

    /**
     * @param string $className
     * @param array $properties Like ['id' => 1]
     * @param array $constructorArgs Like ['id' => 1]
     *
     * @return object
     */
    protected function getEntity($className, array $properties = [], array $constructorArgs = null)
    {
        $reflectionClass = new \ReflectionClass($className);
        $reflectionMethod = null;

        if ($reflectionClass->hasMethod('__construct')) {
            $reflectionMethod = new \ReflectionMethod($className, '__construct');
        }

        if ($reflectionMethod && $reflectionMethod->isPublic()) {
            if (empty($constructorArgs)) {
                $entity = $reflectionClass->newInstance($constructorArgs);
            } else {
                $entity = $reflectionClass->newInstanceArgs($constructorArgs);
            }
        } else {
            $entity = $reflectionClass->newInstanceWithoutConstructor();
        }

        foreach ($properties as $property => $value) {
            $this->setValue($entity, $property, $value);
        }

        return $entity;
    }

    /**
     * @param object $object
     * @param string $property
     * @param string $value
     * @return bool true if success, otherwise false
     */
    protected function setValue($object, $property, $value)
    {
        try {
            $this->getPropertyAccessor()->setValue($object, $property, $value);
        } catch (NoSuchPropertyException $e) {
            $reflectionClass = new \ReflectionClass($object);
            $method = $reflectionClass->getProperty($property);
            $method->setAccessible(true);
            $method->setValue($object, $value);
        } catch (\ReflectionException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param array $data
     * @return array|object
     */
    protected function convertArrayToEntities(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->convertArrayToEntities($value);
            }
        }

        if (!array_key_exists('testEntity', $data)) {
            return $data;
        }

        $className = $data['testEntity'];
        $properties = array_key_exists('testProperties', $data) ? $data['testProperties'] : [];
        $constructorArguments =
            array_key_exists('testConstructorArguments', $data) ? $data['testConstructorArguments'] : null;

        return $this->getEntity($className, $properties, $constructorArguments);
    }
}
