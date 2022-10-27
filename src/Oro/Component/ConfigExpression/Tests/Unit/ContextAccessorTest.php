<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class ContextAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor */
    private $contextAccessor;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(object|array $context, string|PropertyPath $value, ?string $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->contextAccessor->getValue($context, $value));
    }

    public function getValueDataProvider(): array
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
                'context' => new ItemStub(['foo' => 'bar']),
                'value' => new PropertyPath('foo'),
                'expectedValue' => 'bar'
            ],
            'get_nested_property_from_object' => [
                'context' => new ItemStub(['foo' => new ItemStub(['bar' => 'baz'])]),
                'value' => new PropertyPath('foo.bar'),
                'expectedValue' => 'baz'
            ],
            'get_unknown_property' => [
                'context' => new ItemStub(['foo' => 'bar']),
                'value' => new PropertyPath('baz'),
                'expectedValue' => null
            ],
        ];
    }

    /**
     * @dataProvider setValueDataProvider
     * @depends      testGetValue
     */
    public function testSetValue(
        object|array $context,
        PropertyPath $property,
        string|PropertyPath $value,
        ?string $expectedValue
    ) {
        $this->contextAccessor->setValue($context, $property, $value);
        $actualValue = $this->contextAccessor->getValue($context, $property);
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function setValueDataProvider(): array
    {
        return [
            'set_simple_new_property' => [
                'context' => new ItemStub([]),
                'property' => new PropertyPath('test'),
                'value' => 'value',
                'expectedValue' => 'value'
            ],
            'set_simple_existing_property_text_path' => [
                'context' => new ItemStub(['foo' => 'bar']),
                'property' => new PropertyPath('foo'),
                'value' => 'test',
                'expectedValue' => 'test'
            ],
            'set_existing_property_to_new' => [
                'context' => new ItemStub(['foo' => 'bar']),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('foo'),
                'expectedValue' => 'bar'
            ],
            'set_existing_property_to_existing' => [
                'context' => new ItemStub(['foo' => 'bar', 'test' => 'old']),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('foo'),
                'expectedValue' => 'bar'
            ],
            'nested_property_from_object_to_new' => [
                'context' => new ItemStub(['foo' => new ItemStub(['bar' => 'baz'])]),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('foo.bar'),
                'expectedValue' => 'baz'
            ],
            'nested_property_from_object_to_existing' => [
                'context' => new ItemStub(
                    ['test' => 'old', 'foo' => new ItemStub(['bar' => 'baz'])]
                ),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('foo.bar'),
                'expectedValue' => 'baz'
            ],
            'unknown_property' => [
                'context' => new ItemStub(['foo' => 'bar']),
                'property' => new PropertyPath('test'),
                'value' => new PropertyPath('baz'),
                'expectedValue' => null
            ],
        ];
    }

    /**
     * @dataProvider hasValueDataProvider
     */
    public function testHasValue(object $context, PropertyPath $value, bool $expectedValue)
    {
        $this->contextAccessor->hasValue($context, $value);
        $actualValue = $this->contextAccessor->hasValue($context, $value);
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function hasValueDataProvider(): array
    {
        return [
            'not_has' => [
                'context' => new ItemStub([]),
                'value' => new PropertyPath('test'),
                'expectedValue' => false
            ],
            'not_has_nested' => [
                'context' => new ItemStub(['foo' => new ItemStub(['bar' => 'baz'])]),
                'value' => new PropertyPath('data[foo].baz'),
                'expectedValue' => false
            ],
            'has_as_array_syntax' => [
                'context' => new ItemStub(['foo' => 'bar']),
                'value' => new PropertyPath('data[foo]'),
                'expectedValue' => true
            ],
            'has_as_object_syntax' => [
                'context' => new ItemStub(['foo' => 'bar']),
                'value' => new PropertyPath('data[foo]'),
                'expectedValue' => true
            ],
            'has_nested' => [
                'context' => new ItemStub(['foo' => new ItemStub(['bar' => 'baz'])]),
                'value' => new PropertyPath('data[foo].data'),
                'expectedValue' => true
            ],
            'has_nested_nested' => [
                'context' => new ItemStub(['foo' => new ItemStub(['bar' => 'baz'])]),
                'value' => new PropertyPath('data[foo].data.bar'),
                'expectedValue' => true
            ],
        ];
    }

    public function testGetValueNoSuchProperty()
    {
        $context = new ItemStub([]);
        $value = new PropertyPath('test');

        $propertyAccessor = $this->createMock(PropertyAccessor::class);
        $propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($context, $value)
            ->willThrowException(new NoSuchPropertyException('No such property'));

        ReflectionUtil::setPropertyValue($this->contextAccessor, 'propertyAccessor', $propertyAccessor);

        $this->assertNull($this->contextAccessor->getValue($context, $value));
    }
}
