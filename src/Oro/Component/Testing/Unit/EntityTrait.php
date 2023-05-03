<?php

namespace Oro\Component\Testing\Unit;

use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\Testing\Unit\PropertyAccess\PropertyAccessTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Trait that helps developer to work with Entity for the testing purpose without mocking them
 */
trait EntityTrait
{
    use PropertyAccessTrait;

    /**
     * Helps to create entity object with specified set of properties.
     * Uses reflection to set not accessible properties like private/protected
     *
     * @param string $className
     * @param array  $properties Like ['id' => 1]
     * @param array  $constructorArgs Like ['id' => 1]
     *
     * @return object
     *
     * @template T
     * @psalm-param class-string<T> $className
     * @psalm-return T
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
                $entity = $reflectionClass->newInstance();
            } else {
                $entity = $reflectionClass->newInstanceArgs($constructorArgs);
            }
        } else {
            $entity = $reflectionClass->newInstanceWithoutConstructor();
        }
        foreach ($properties as $property => $value) {
            $this->setValue($entity, $property, $value);
        }
        $this->cleanExtendEntityStorage($entity);

        return $entity;
    }

    /**
     * Allows to set any property (accessible or not) of the object
     *
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    protected function setValue($object, $property, $value)
    {
        try {
            $this->getPropertyAccessor()->setValue($object, $property, $value);
        } catch (NoSuchPropertyException $e) {
            $reflectionClass = new EntityReflectionClass($object);

            // Looking for the property in parent classes
            // because it's impossible to get parent properties from the derived class
            while (!$reflectionClass->hasProperty($property)
                && $parentReflectionClass = $reflectionClass->getParentClass()
            ) {
                $reflectionClass = $parentReflectionClass;
            }

            $method = $reflectionClass->getProperty($property);
            $method->setAccessible(true);
            $method->setValue($object, $value);
        }
        $this->cleanExtendEntityStorage($object);
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

    private function cleanExtendEntityStorage(object|string $objectOrClass): void
    {
        /** ExtendEntityInterface $objectOrClass */
        if (!$this instanceof BaseWebTestCase && ExtendHelper::isExtendEntity($objectOrClass)) {
            $objectOrClass->cleanExtendEntityStorage();
        }
    }
}
