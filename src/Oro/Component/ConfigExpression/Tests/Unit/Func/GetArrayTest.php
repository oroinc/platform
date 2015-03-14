<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\PropertyAccess\PropertyPath;

class GetArrayTest extends \PHPUnit_Framework_TestCase
{
    /** @var Func\GetArray */
    protected $function;

    protected function setUp()
    {
        $this->function = new Func\GetArray();
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
            'empty'          => [
                'options'        => [],
                'context'        => [],
                'expectedResult' => []
            ],
            'several_values' => [
                'options'        => [new PropertyPath('foo'), 'const', new Condition\True()],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => ['bar', 'const', true]
            ],
        ];
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
                'options'  => [new PropertyPath('foo'), 'const', new Condition\True()],
                'message'  => null,
                'expected' => [
                    '@array' => [
                        'parameters' => [
                            '$foo',
                            'const',
                            ['@true' => null]
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('foo'), 'const', new Condition\True()],
                'message'  => 'Test',
                'expected' => [
                    '@array' => [
                        'message'    => 'Test',
                        'parameters' => [
                            '$foo',
                            'const',
                            ['@true' => null]
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
                'options'  => [new PropertyPath('foo'), 'const', new Condition\True()],
                'message'  => null,
                'expected' => '$factory->create(\'array\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'])'
                    . ', \'const\', $factory->create(\'true\', [])])'
            ],
            [
                'options'  => [new PropertyPath('foo'), 'const', new Condition\True()],
                'message'  => 'Test',
                'expected' => '$factory->create(\'array\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'])'
                    . ', \'const\', $factory->create(\'true\', [])])'
                    . '->setMessage(\'Test\')'
            ]
        ];
    }
}
