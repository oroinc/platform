<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Func;

use Oro\Component\ConfigExpression\Condition\TrueCondition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Func;
use Oro\Component\ConfigExpression\Func\GetValue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetValueTest extends TestCase
{
    private Func\GetValue $function;

    #[\Override]
    protected function setUp(): void
    {
        $this->function = new GetValue();
        $this->function->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, array $context, string|bool $expectedResult): void
    {
        $this->assertSame($this->function, $this->function->initialize($options));
        $this->assertEquals($expectedResult, $this->function->evaluate($context));
    }

    public function evaluateDataProvider(): array
    {
        return [
            'get'                                         => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => 'bar'
            ],
            'get_with_default'                            => [
                'options'        => [new PropertyPath('foo'), 'baz'],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => 'bar'
            ],
            'get_with_default_and_value_null'             => [
                'options'        => [new PropertyPath('foo'), 'baz'],
                'context'        => ['foo' => null],
                'expectedResult' => 'baz'
            ],
            'get_with_default_and_no_value'               => [
                'options'        => [new PropertyPath('foo'), 'baz'],
                'context'        => [],
                'expectedResult' => 'baz'
            ],
            'get_with_expr'                               => [
                'options'        => [new TrueCondition()],
                'context'        => [],
                'expectedResult' => true
            ],
            'get_constant'                                => [
                'options'        => ['foo'],
                'context'        => [],
                'expectedResult' => 'foo'
            ],
            'get_constant_with_default_and_constant_null' => [
                'options'        => [null, 'baz'],
                'context'        => [],
                'expectedResult' => 'baz'
            ]
        ];
    }

    public function testInitializeFailsWhenEmptyOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 or 2 elements, but 0 given.');

        $this->function->initialize([]);
    }

    public function testInitializeFailsWhenTooManyOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 or 2 elements, but 3 given.');

        $this->function->initialize([1, 2, 3]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, ?string $message, array $expected): void
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
                'options'  => [new PropertyPath('foo'), null],
                'message'  => null,
                'expected' => [
                    '@value' => [
                        'parameters' => [
                            '$foo',
                            null
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('foo'), new PropertyPath('bar')],
                'message'  => null,
                'expected' => [
                    '@value' => [
                        'parameters' => [
                            '$foo',
                            '$bar'
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

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(array $options, ?string $message, string $expected): void
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
                'options'  => [new PropertyPath('foo')],
                'message'  => null,
                'expected' => '$factory->create(\'value\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false], [false])'
                    . '])'
            ],
            [
                'options'  => [new PropertyPath('foo'), null],
                'message'  => null,
                'expected' => '$factory->create(\'value\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false], [false])'
                    . ', null])'
            ],
            [
                'options'  => [new PropertyPath('foo'), new PropertyPath('bar')],
                'message'  => null,
                'expected' => '$factory->create(\'value\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false], [false])'
                    . ', new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'bar\', [\'bar\'], [false], [false])'
                    . '])'
            ],
            [
                'options'  => [new PropertyPath('foo')],
                'message'  => 'Test',
                'expected' => '$factory->create(\'value\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false], [false])'
                    . '])->setMessage(\'Test\')'
            ]
        ];
    }
}
