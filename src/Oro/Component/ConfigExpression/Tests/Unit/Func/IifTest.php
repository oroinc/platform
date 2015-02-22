<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\ConfigExpression\PropertyAccess\PropertyPath;

class IifTest extends \PHPUnit_Framework_TestCase
{
    /** @var Func\Iif */
    protected $function;

    protected function setUp()
    {
        $this->function = new Func\Iif();
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
            'true_expr'  => [
                'options'        => [new Condition\True(), new PropertyPath('foo'), new PropertyPath('bar')],
                'context'        => ['foo' => 'true', 'bar' => 'false'],
                'expectedResult' => 'true'
            ],
            'false_expr' => [
                'options'        => [new Condition\False(), new PropertyPath('foo'), new PropertyPath('bar')],
                'context'        => ['foo' => 'true', 'bar' => 'false'],
                'expectedResult' => 'false'
            ]
        ];
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 3 elements, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->function->initialize([]);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid expression type. Expected "Oro\Component\ConfigExpression\ExpressionInterface", "integer" given.
     */
    // @codingStandardsIgnoreEnd
    public function testInitializeFailsWhenFirstArgIsNotExpression()
    {
        $this->function->initialize([1, 2, 3]);
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
                'options'  => [new Condition\True(), new PropertyPath('foo'), new PropertyPath('bar')],
                'message'  => null,
                'expected' => [
                    '@iif' => [
                        'parameters' => [
                            ['@true' => null],
                            '$foo',
                            '$bar'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new Condition\True(), new PropertyPath('foo'), new PropertyPath('bar')],
                'message'  => 'Test',
                'expected' => [
                    '@iif' => [
                        'message'    => 'Test',
                        'parameters' => [
                            ['@true' => null],
                            '$foo',
                            '$bar'
                        ]
                    ]
                ]
            ]
        ];
    }
}
