<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Func;
use Symfony\Component\PropertyAccess\PropertyPath;

class IifTest extends \PHPUnit\Framework\TestCase
{
    /** @var Func\Iif */
    protected $function;

    protected function setUp(): void
    {
        $this->function = new Func\Iif();
        $this->function->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, array $context, string $expectedResult)
    {
        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function evaluateDataProvider(): array
    {
        return [
            'true_expr'        => [
                'options'        => [new Condition\TrueCondition(), new PropertyPath('foo'), new PropertyPath('bar')],
                'context'        => ['foo' => 'true', 'bar' => 'false'],
                'expectedResult' => 'true'
            ],
            'false_expr'       => [
                'options'        => [new Condition\FalseCondition(), new PropertyPath('foo'), new PropertyPath('bar')],
                'context'        => ['foo' => 'true', 'bar' => 'false'],
                'expectedResult' => 'false'
            ],
            'short_true_expr'  => [
                'options'        => [new PropertyPath('foo'), new PropertyPath('bar')],
                'context'        => ['foo' => 'fooValue', 'bar' => 'barValue'],
                'expectedResult' => 'fooValue'
            ],
            'short_false_expr' => [
                'options'        => [new PropertyPath('foo'), new PropertyPath('bar')],
                'context'        => ['foo' => null, 'bar' => 'barValue'],
                'expectedResult' => 'barValue'
            ]
        ];
    }

    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 2 or 3 elements, but 0 given.');

        $this->function->initialize([]);
    }

    public function testInitializeFailsWhenTooManyOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 2 or 3 elements, but 4 given.');

        $this->function->initialize([1, 2, 3, 4]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, ?string $message, array $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'options'  => [new Condition\TrueCondition(), new PropertyPath('foo'), new PropertyPath('bar')],
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
                'options'  => [new PropertyPath('foo'), new PropertyPath('bar')],
                'message'  => null,
                'expected' => [
                    '@iif' => [
                        'parameters' => [
                            '$foo',
                            '$bar'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new Condition\TrueCondition(), new PropertyPath('foo'), new PropertyPath('bar')],
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

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(array $options, ?string $message, string $expected)
    {
        $this->function->initialize($options);
        if ($message !== null) {
            $this->function->setMessage($message);
        }
        $actual = $this->function->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'options'  => [new Condition\TrueCondition(), new PropertyPath('foo'), new PropertyPath('bar')],
                'message'  => null,
                'expected' => '$factory->create(\'iif\', [$factory->create(\'true\', []), '
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false]), '
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'bar\', [\'bar\'], [false])'
                    . '])'
            ],
            [
                'options'  => [new PropertyPath('foo'), new PropertyPath('bar')],
                'message'  => null,
                'expected' => '$factory->create(\'iif\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false]), '
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'bar\', [\'bar\'], [false])'
                    . '])'
            ],
            [
                'options'  => [new Condition\TrueCondition(), new PropertyPath('foo'), new PropertyPath('bar')],
                'message'  => 'Test',
                'expected' => '$factory->create(\'iif\', [$factory->create(\'true\', []), '
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false]), '
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'bar\', [\'bar\'], [false])'
                    . '])->setMessage(\'Test\')'
            ]
        ];
    }
}
