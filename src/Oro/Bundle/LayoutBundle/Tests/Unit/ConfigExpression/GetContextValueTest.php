<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Oro\Bundle\LayoutBundle\ConfigExpression\GetContextValue;
use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;

class GetContextValueTest extends \PHPUnit\Framework\TestCase
{
    /** @var GetContextValue */
    protected $function;

    protected function setUp()
    {
        $this->function = new GetContextValue();
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
            'get'                                         => [
                'options'        => ['foo'],
                'context' => ['context' => ['foo' => 'bar']],
                'expectedResult' => 'bar'
            ],
            'get_with_default'                            => [
                'options'        => ['foo', 'baz'],
                'context'        => ['context' => ['foo' => 'bar']],
                'expectedResult' => 'bar'
            ],
            'get_with_default_and_value_null'             => [
                'options'        => ['foo', 'baz'],
                'context'        => ['context' => ['foo' => null]],
                'expectedResult' => 'baz'
            ],
            'get_with_default_and_no_value'               => [
                'options'        => ['foo', 'baz'],
                'context'        => [],
                'expectedResult' => 'baz'
            ],
        ];
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 or 2 elements, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->function->initialize([]);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 or 2 elements, but 3 given.
     */
    public function testInitializeFailsWhenTooManyOptions()
    {
        $this->function->initialize([1, 2, 3]);
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage The first option should be a string, but integer given.
     */
    public function testInitializeFailsWhenFirstOptionIsNotString()
    {
        $this->function->initialize([4, 5]);
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
                'options'  => ['foo'],
                'message'  => null,
                'expected' => [
                    '@context' => [
                        'parameters' => [
                            'foo'
                        ]
                    ]
                ]
            ],
            [
                'options'  => ['foo', null],
                'message'  => null,
                'expected' => [
                    '@context' => [
                        'parameters' => [
                            'foo',
                            null
                        ]
                    ]
                ]
            ],
            [
                'options'  => ['foo'],
                'message'  => 'Test',
                'expected' => [
                    '@context' => [
                        'message'    => 'Test',
                        'parameters' => [
                            'foo'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile($options, $message, $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider()
    {
        return [
            [
                'options'  => ['foo'],
                'message'  => null,
                'expected' => '$factory->create(\'context\', ['
                    .'\'foo\''
                    .'])'
            ],
            [
                'options'  => ['foo', null],
                'message'  => null,
                'expected' => '$factory->create(\'context\', ['
                    .'\'foo\''
                    .', null])'
            ],
            [
                'options'  => ['foo'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'context\', ['
                    .'\'foo\''
                    .'])->setMessage(\'Test\')'
            ]
        ];
    }
}
