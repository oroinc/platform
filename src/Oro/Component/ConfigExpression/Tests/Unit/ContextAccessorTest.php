<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyPath;

class ContextAccessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
    }

    /**
     * @dataProvider getValueDataProvider
     * @param object|array $context
     * @param string|PropertyPath $value
     * @param string $expectedValue
     */
    public function testGetValue($context, $value, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->contextAccessor->getValue($context, $value));
    }

    /**
     * @return array
     */
    public function getValueDataProvider()
    {
        return [
            'get_simple_value' => [
                'context' => ['foo' => 'bar'],
                'value' => 'test',
                'expectedValue' => 'test'
            ],
            'get_property_from_array' => [
                'context' => ['foo' => 'bar'],
                'value' => new PropertyPath('[foo]'),
                'expectedValue' => 'bar'
            ],
            'get_property_from_object' => [
                'context' => $this->createObject(['foo' => 'bar']),
                'value' => new PropertyPath('foo'),
                'expectedValue' => 'bar'
            ],
            'get_nested_property_from_object' => [
                'context' => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'value' => new PropertyPath('foo.bar'),
                'expectedValue' => 'baz'
            ],
            'get_unknown_property' => [
                'context' => $this->createObject(['foo' => 'bar']),
                'value' => new PropertyPath('baz'),
                'expectedValue' => null
            ],
        ];
    }

    /**
     * @dataProvider setValueDataProvider
     * @depends      testGetValue
     * @param object|array $context
     * @param PropertyPath $property
     * @param string|PropertyPath $value
     * @param string $expectedValue
     */
    public function testSetValue($context, PropertyPath $property, $value, $expectedValue)
    {
        $this->contextAccessor->setValue($context, $property, $value);
        $actualValue = $this->contextAccessor->getValue($context, $property);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @return array
     */
    public function setValueDataProvider()
    {
        return [
            'set_simple_new_property' => [
                'context' => $this->createObject([]),
                'property' => new PropertyPath('test'),
                'value' => 'value',
                'expectedValue' => 'value'
            ],
            'set_simple_existing_property_text_path' => [
                'context' => $this->createObject(['foo' => 'bar']),
                'property' => new PropertyPath('foo'),
                'value' => 'test',
                'expectedValue' => 'test'
            ],
            'set_existing_property_to_new' => [
                'context' => $this->createObject(['foo' => 'bar']),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('foo'),
                'expectedValue' => 'bar'
            ],
            'set_existing_property_to_existing' => [
                'context' => $this->createObject(['foo' => 'bar', 'test' => 'old']),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('foo'),
                'expectedValue' => 'bar'
            ],
            'nested_property_from_object_to_new' => [
                'context' => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('foo.bar'),
                'expectedValue' => 'baz'
            ],
            'nested_property_from_object_to_existing' => [
                'context' => $this->createObject(
                    ['test' => 'old', 'foo' => $this->createObject(['bar' => 'baz'])]
                ),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('foo.bar'),
                'expectedValue' => 'baz'
            ],
            'unknown_property' => [
                'context' => $this->createObject(['foo' => 'bar']),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('baz'),
                'expectedValue' => null
            ],
        ];
    }

    /**
     * @dataProvider hasValueDataProvider
     *
     * @param mixed $context
     * @param mixed $value
     * @param mixed $expectedValue
     */
    public function testHasValue($context, $value, $expectedValue)
    {
        $this->contextAccessor->hasValue($context, $value);
        $actualValue = $this->contextAccessor->hasValue($context, $value);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @return array
     */
    public function hasValueDataProvider()
    {
        return [
            'not_has' => [
                'context' => $this->createObject([]),
                'value' => new PropertyPath('test'),
                'expectedValue' => false
            ],
            'not_has_nested' => [
                'context' => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'value' => new PropertyPath('data[foo].baz'),
                'expectedValue' => false
            ],
            'has_as_array_syntax' => [
                'context' => $this->createObject(['foo' => 'bar']),
                'value' => new PropertyPath('data[foo]'),
                'expectedValue' => true
            ],
            'has_as_object_syntax' => [
                'context' => $this->createObject(['foo' => 'bar']),
                'value' => new PropertyPath('data[foo]'),
                'expectedValue' => true
            ],
            'has_nested' => [
                'context' => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'value' => new PropertyPath('data[foo].data'),
                'expectedValue' => true
            ],
            'has_nested_nested' => [
                'context' => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'value' => new PropertyPath('data[foo].data.bar'),
                'expectedValue' => true
            ],
        ];
    }

    public function testGetValueNoSuchProperty()
    {
        $context = $this->createObject([]);
        $value = new PropertyPath('test');

        $propertyAccessor = $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyAccessor')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($context, $value)
            ->will($this->throwException(new NoSuchPropertyException('No such property')));

        $propertyAccessorReflection = new \ReflectionProperty(
            'Oro\Component\ConfigExpression\ContextAccessor',
            'propertyAccessor'
        );
        $propertyAccessorReflection->setAccessible(true);
        $propertyAccessorReflection->setValue($this->contextAccessor, $propertyAccessor);

        $this->assertNull($this->contextAccessor->getValue($context, $value));
    }

    /**
     * @param array $data
     *
     * @return ItemStub
     */
    protected function createObject(array $data)
    {
        return new ItemStub($data);
    }
}
