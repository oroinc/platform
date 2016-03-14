<?php

namespace Oro\Component\Testing\Unit;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\Testing\Unit\PropertyAccess\CollectionAccessor;
use Oro\Component\Testing\Unit\Constraint\PropertyGetterReturnsDefaultValue;
use Oro\Component\Testing\Unit\Constraint\PropertyGetterReturnsSetValue;

trait EntityTestCaseTrait
{
    /**
     * @param object $instance
     * @param string $propertyName
     * @param string $message
     */
    public static function assertPropertyGetterReturnsDefaultValue($instance, $propertyName, $message = '')
    {
        \PHPUnit_Framework_TestCase::assertThat(
            $instance,
            self::propertyGetterReturnsDefaultValue($propertyName),
            $message
        );
    }

    /**
     * Returns a PropertyGetterReturnsDefaultValue matcher object.
     *
     * @param string $propertyName
     * @return PropertyGetterReturnsDefaultValue
     */
    public static function propertyGetterReturnsDefaultValue($propertyName)
    {
        return new PropertyGetterReturnsDefaultValue(
            $propertyName
        );
    }

    /**
     * @param object $instance
     * @param string $propertyName
     * @param mixed $testValue
     * @param string $message
     */
    public static function assertPropertyGetterReturnsSetValue($instance, $propertyName, $testValue, $message = '')
    {
        \PHPUnit_Framework_TestCase::assertThat(
            $instance,
            self::propertyGetterReturnsSetValue($propertyName, $testValue),
            $message
        );
    }

    /**
     * Returns a PropertyGetterReturnsSetValue matcher object.
     *
     * @param string $propertyName
     * @param mixed $testValue
     * @return PropertyGetterReturnsSetValue
     */
    public static function propertyGetterReturnsSetValue($propertyName, $testValue)
    {
        return new PropertyGetterReturnsSetValue(
            $propertyName,
            $testValue
        );
    }

    /**
     * Performance assertPropertyGetterReturnsDefaultValue and assertPropertyGetterReturnsSetValue assertions
     * on specified properties.
     *
     * @param object $instance
     * @param array $properties
     * <pre>
     * Example:
     * [1] => Array(
     *   [1] => 'someProperty', // property name
     *   [2] => 123.45,         // a value it can be tested with
     *   [3] => true            // property has some default value
     * )
     * [2] => Array(
     *   [1] => 'anotherProperty',
     *   [2] => SomeComplexObject(...),
     *   [3] => false   // do not test default value (e.g. if the property is initialized in the constructor,
     *                  // or is lazy loaded by its getter)
     * )
     * </pre>
     */
    public static function assertPropertyAccessors($instance, $properties)
    {
        foreach ($properties as $property) {
            $testInstance = clone $instance;

            $propertyName = $property[0];
            $testValue = $property[1];
            $testDefaultValue = isset($property[2]) ? $property[2] : true;

            if ($testDefaultValue) {
                self::assertPropertyGetterReturnsDefaultValue($testInstance, $propertyName);
            }
            self::assertPropertyGetterReturnsSetValue($testInstance, $propertyName, $testValue);
        }
    }

    /**
     * Performance assertPropertyCollection assertion on specified properties
     *
     * @param object $instance
     * @param array $properties
     * <pre>
     * Example:
     * [0] => Array(
     *      [0] => 'numbers', // property name - collection
     *      [1] => 123.45,    // a value - will be tested as item of a collection
     * )
     * [1] => Array(
     *      [0] => 'someObjects',
     *      [1] => SomeObject(),
     * )
     * </pre>
     */
    public static function assertPropertyCollections($instance, array $properties)
    {
        foreach ($properties as $property) {
            $testInstance = clone $instance;

            list($propertyName, $testItem) = $property;

            self::assertPropertyCollection($testInstance, $propertyName, $testItem);
        }
    }

    /**
     * @param object $instance
     * @param string $propertyName
     * @param mixed $testItem
     */
    public static function assertPropertyCollection($instance, $propertyName, $testItem)
    {
        $propertyAccess = new CollectionAccessor($instance, $propertyName);

        // Check default value
        \PHPUnit_Framework_TestCase::assertInstanceOf(
            'Doctrine\Common\Collections\Collection',
            $propertyAccess->getItems(),
            $propertyName . ': Default value must be instance of Collection'
        );

        // Check default size
        \PHPUnit_Framework_TestCase::assertCount(
            0,
            $propertyAccess->getItems(),
            $propertyName . ': Default collection size must be 0'
        );

        // Add first item
        \PHPUnit_Framework_TestCase::assertSame(
            $instance,
            $propertyAccess->addItem($testItem),
            sprintf(
                '%s::%s() - must return %s',
                ClassUtils::getClass($instance),
                $propertyAccess->getAddItemMethod(),
                ClassUtils::getClass($instance)
            )
        );

        // Check added item
        \PHPUnit_Framework_TestCase::assertCount(
            1,
            $propertyAccess->getItems(),
            $propertyName . ': After add item - collection size must be 1'
        );

        \PHPUnit_Framework_TestCase::assertInstanceOf(
            'Doctrine\Common\Collections\Collection',
            $propertyAccess->getItems(),
            $propertyName . ': After addition of a first item - property value must be instance of Collection'
        );

        \PHPUnit_Framework_TestCase::assertEquals(
            [$testItem],
            $propertyAccess->getItems()->toArray(),
            $propertyName . ': After addition of a first item - collections must be equals'
        );

        // Add already added item
        $propertyAccess->addItem($testItem);
        \PHPUnit_Framework_TestCase::assertCount(
            1,
            $propertyAccess->getItems(),
            $propertyName . ': After addition already added item - collection size must be same and equal 1'
        );

        // Remove item
        \PHPUnit_Framework_TestCase::assertSame(
            $instance,
            $propertyAccess->removeItem($testItem),
            sprintf(
                '%s:%s() - must return %s',
                ClassUtils::getClass($instance),
                $propertyAccess->getRemoveItemMethod(),
                ClassUtils::getClass($instance)
            )
        );

        \PHPUnit_Framework_TestCase::assertCount(
            0,
            $propertyAccess->getItems(),
            $propertyName . ': After removal of a single item - collection size must be 0'
        );

        // Remove already removed item
        $propertyAccess->removeItem($testItem);
        \PHPUnit_Framework_TestCase::assertCount(
            0,
            $propertyAccess->getItems(),
            $propertyName . ': After removal already removed item - collection size must be same and equal 0'
        );

        \PHPUnit_Framework_TestCase::assertNotContains(
            $testItem,
            $propertyAccess->getItems()->toArray(),
            $propertyName . ': After removal of a single item - collection must not contains test item'
        );
    }
}
