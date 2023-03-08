<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend;

use Oro\Bundle\EntityExtendBundle\EntityExtend\PropertyAccessorWithDotArraySyntax;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\TestEnum;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class PropertyAccessorWithDotArraySyntaxTest extends WebTestCase
{
    protected PropertyAccessorInterface $propertyAccessor;

    protected function setUp(): void
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax();
    }

    /**
     * @dataProvider getArrayValueDataProvider
     */
    public function testGetArrayValue(object|array $objectOrArray, string $name, mixed $expected): void
    {
        $this->assertSame($expected, $this->propertyAccessor->getValue($objectOrArray, $name));
    }

    public function getArrayValueDataProvider(): array
    {
        return [
            'access array element' => [
                'objectOrArray' => [
                    'item1' => 'value1',
                    'item2' => 'value 2'
                ],
                'name' => 'item1',
                'expected' => 'value1'
            ],
            'access array element with dept' => [
                'objectOrArray' => [
                    'item1' => [
                        'item2' => 'value 2'
                    ]
                ],
                'name' => 'item1.item2',
                'expected' => 'value 2'
            ],
            'access array element undefined' => [
                'objectOrArray' => [
                    'item1' => [
                        'item2' => 'value 2'
                    ]
                ],
                'name' => 'item1.item3',
                'expected' => null
            ]
        ];
    }

    public function testTryGetValueWithThrowOnInvalidIndex(): void
    {
        $array = ['name' => 'value'];
        $undefinedKey = 'undefinedKey';
        self::expectException(NoSuchPropertyException::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            throw: PropertyAccessorWithDotArraySyntax::THROW_ON_INVALID_INDEX
        );
        $propertyAccessor->getValue($array, $undefinedKey);
    }

    /**
     * @dataProvider objectGetValueDataProvider
     */
    public function testObjectGetValue(object|array $objectOrArray, string $name, mixed $expected): void
    {
        $this->assertSame($expected, $this->propertyAccessor->getValue($objectOrArray, $name));
    }

    public function objectGetValueDataProvider(): array
    {
        return [
            'get property' => [
                'objectOrArray' => new TestEnum(1, 'testName'),
                'name' => 'name',
                'expected' => 'testName'
            ],
            'get property with upper property' => [
                'objectOrArray' => new TestEnum(1, 'testName'),
                'name' => 'Name',
                'expected' => 'testName'
            ],
            'get property by method name' => [
                'objectOrArray' => new TestEnum(1, 'testName'),
                'name' => 'getName',
                'expected' => 'testName'
            ],
        ];
    }

    /**
     * @dataProvider getObjectUndefinedValueDataProvider
     */
    public function testGetObjectUndefinedValue(object|array $objectOrArray, string $name, string $className): void
    {
        self::expectException(NoSuchPropertyException::class);
        self::expectExceptionMessage(
            sprintf('Can\'t get a way to read the property "%s" in class "%s".', $name, $className)
        );

        $this->assertNull($this->propertyAccessor->getValue($objectOrArray, $name));
    }

    public function getObjectUndefinedValueDataProvider(): array
    {
        return [
            'get undefined object property' => [
                'objectOrArray' => new TestEnum(1, 'testName'),
                'name' => 'undefinedProperty',
                'className' => TestEnum::class
            ],
            'try  get undefined object method' => [
                'objectOrArray' => new TestEnum(1, 'testName'),
                'name' => 'getUndefinedMethod',
                'className' => TestEnum::class
            ],
        ];
    }

    public function testTryGetValueWithThrowOnInvalidProperty(): void
    {
        $object = new TestEnum(1, 'testName');
        $undefinedProperty = 'undefinedProperty';
        self::expectException(NoSuchPropertyException::class);
        self::expectExceptionMessage(
            sprintf(
                'Can\'t get a way to read the property "%s" in class "%s".',
                $undefinedProperty,
                TestEnum::class
            )
        );
        $propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            throw: PropertyAccessorWithDotArraySyntax::THROW_ON_INVALID_PROPERTY_PATH
        );
        $propertyAccessor->getValue($object, $undefinedProperty);
    }

    public function testTrySetValue(): void
    {
        $object = new TestEnum(1, 'testName');
        $this->propertyAccessor->setValue($object, 'name', 'newValue');

        self::assertSame('newValue', $object->getName());
    }

    public function testTrySetValueForArray(): void
    {
        $array = ['testKey' => 'testValue'];
        $this->propertyAccessor->setValue($array, 'newKey', 'newValue');
        $expectedValue = [
            ...$array,
            'newKey' => 'newValue'
        ];
        self::assertSame($expectedValue, $array);
    }

    public function testTrySetValueWithThrowOnInvalidProperty(): void
    {
        $object = new TestEnum(1, 'testName');
        $undefinedProperty = 'undefinedProperty';
        self::expectException(NoSuchPropertyException::class);
        self::expectExceptionMessage(
            sprintf(
                'There is no %s property at %s.',
                $undefinedProperty,
                TestEnum::class
            )
        );
        $propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            throw: PropertyAccessorWithDotArraySyntax::THROW_ON_INVALID_PROPERTY_PATH
        );
        $propertyAccessor->setValue($object, $undefinedProperty, 'newValue');
    }

    public function testRemoveProperty(): void
    {
        $object = new TestEnum(1, 'testName');
        $this->propertyAccessor->remove($object, 'name');

        self::assertSame('', $object->getName());
    }

    public function testRemoveArray(): void
    {
        $array = ['testKey' => 'testValue'];
        $this->propertyAccessor->remove($array, 'testKey');

        self::assertSame([], $array);
    }
}
