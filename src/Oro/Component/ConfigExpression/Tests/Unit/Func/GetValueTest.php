<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\ConfigExpression\PropertyAccess\PropertyPath;

class GetValueTest extends \PHPUnit_Framework_TestCase
{
    /** @var Func\GetValue */
    protected $function;

    protected function setUp()
    {
        $this->function = new Func\GetValue();
        $this->function->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, $context, $expectedResult)
    {
        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function evaluateDataProvider()
    {
        return [
            'get'           => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => 'bar'
            ],
            'get_with_expr' => [
                'options'        => [new Condition\True()],
                'context'        => [],
                'expectedResult' => true
            ],
            'get_constant'  => [
                'options'        => ['foo'],
                'context'        => [],
                'expectedResult' => 'foo'
            ]
        ];
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->function->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 2 given.
     */
    public function testInitializeFailsWhenTooManyOptions()
    {
        $this->function->initialize([1, 2]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($options, $message, $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider()
    {
        return [
            [
                'options'  => [new PropertyPath('foo')],
                'message'  => null,
                'expected' => [
                    '@value' => [
                        'parameters' => [
                            '$foo'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('foo')],
                'message'  => 'Test',
                'expected' => [
                    '@value' => [
                        'message'    => 'Test',
                        'parameters' => [
                            '$foo'
                        ]
                    ]
                ]
            ]
        ];
    }
}
