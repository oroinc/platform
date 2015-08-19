<?php

namespace Oro\Component\ConfigExpression\Tests\Unit;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class ContextAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextAccessor */
    protected $contextAccessor;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue($context, $property, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->contextAccessor->getValue($context, $property));
    }

    public function getValueDataProvider()
    {
        return [
            'first_level'                => [
                'context'       => $this->createObject(['foo' => 'bar']),
                'property'      => new PropertyPath('foo'),
                'expectedValue' => 'bar'
            ],
            'nested'                     => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('foo.bar'),
                'expectedValue' => 'baz'
            ],
            'nested_by_first_level_data' => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('data.foo.bar'),
                'expectedValue' => 'baz'
            ],
            'nested_by_data'             => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('data.foo.data.bar'),
                'expectedValue' => 'baz'
            ]
        ];
    }

    /**
     * @dataProvider setValueDataProvider
     * @depends      testGetValue
     */
    public function testSetValue($context, $property, $value, $expectedValue)
    {
        $this->contextAccessor->setValue($context, $property, $value);
        $actualValue = $this->contextAccessor->getValue($context, $property);
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function setValueDataProvider()
    {
        return [
            'first_level'                => [
                'context'       => $this->createObject(['foo' => 'bar']),
                'property'      => new PropertyPath('foo'),
                'value'         => 'test',
                'expectedValue' => 'test'
            ],
            'nested'                     => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('foo.bar'),
                'value'         => 'test',
                'expectedValue' => 'test'
            ],
            'nested_by_first_level_data' => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('data.foo.bar'),
                'value'         => 'test',
                'expectedValue' => 'test'
            ],
            'nested_by_data'             => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('data.foo.data.bar'),
                'value'         => 'test',
                'expectedValue' => 'test'
            ]
        ];
    }

    /**
     * @dataProvider hasValueDataProvider
     * @depends      testGetValue
     */
    public function testHasValue($context, $property, $expectedValue)
    {
        $actualValue = $this->contextAccessor->hasValue($context, $property);
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function hasValueDataProvider()
    {
        return [
            'has'                                => [
                'context'       => $this->createObject(['foo' => null]),
                'property'      => new PropertyPath('foo'),
                'expectedValue' => true
            ],
            'not_has'                            => [
                'context'       => $this->createObject([]),
                'property'      => new PropertyPath('foo'),
                'expectedValue' => false
            ],
            'has_nested'                         => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('foo.bar'),
                'expectedValue' => true
            ],
            'not_has_nested'                     => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('foo.baz'),
                'expectedValue' => false
            ],
            'has_nested_by_first_level_data'     => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('data.foo.bar'),
                'expectedValue' => true
            ],
            'not_has_nested_by_first_level_data' => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('data.foo.baz'),
                'expectedValue' => false
            ],
            'has_nested_by_data'                 => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('data.foo.data.bar'),
                'expectedValue' => true
            ],
            'not_has_nested_by_data'             => [
                'context'       => $this->createObject(['foo' => $this->createObject(['bar' => 'baz'])]),
                'property'      => new PropertyPath('data.foo.data.baz'),
                'expectedValue' => false
            ]
        ];
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage The property "test" cannot be got by "__get" method because "__isset" method returns false. Class "Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub".
     */
    // @codingStandardsIgnoreEnd
    public function testGetValueForUnknownProperty()
    {
        $this->contextAccessor->getValue(
            $this->createObject([]),
            new PropertyPath('test')
        );
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
