<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\ConfigExpression;

use Oro\Bundle\LayoutBundle\ConfigExpression\GetContextValue;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class GetContextValueTest extends \PHPUnit\Framework\TestCase
{
    /** @var GetContextValue */
    private $function;

    protected function setUp(): void
    {
        $this->function = new GetContextValue();
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
            'get'                             => [
                'options'        => ['foo'],
                'context'        => ['context' => ['foo' => 'bar']],
                'expectedResult' => 'bar'
            ],
            'get_with_default'                => [
                'options'        => ['foo', 'baz'],
                'context'        => ['context' => ['foo' => 'bar']],
                'expectedResult' => 'bar'
            ],
            'get_with_default_and_value_null' => [
                'options'        => ['foo', 'baz'],
                'context'        => ['context' => ['foo' => null]],
                'expectedResult' => 'baz'
            ],
            'get_with_default_and_no_value'   => [
                'options'        => ['foo', 'baz'],
                'context'        => [],
                'expectedResult' => 'baz'
            ],
        ];
    }

    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 or 2 elements, but 0 given.');

        $this->function->initialize([]);
    }

    public function testInitializeFailsWhenTooManyOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 or 2 elements, but 3 given.');

        $this->function->initialize([1, 2, 3]);
    }

    public function testInitializeFailsWhenFirstOptionIsNotString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The first option should be a string, but integer given.');

        $this->function->initialize([4, 5]);
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
                'options'  => ['foo'],
                'message'  => null,
                'expected' => '$factory->create(\'context\', ['
                    . '\'foo\''
                    . '])'
            ],
            [
                'options'  => ['foo', null],
                'message'  => null,
                'expected' => '$factory->create(\'context\', ['
                    . '\'foo\''
                    . ', null])'
            ],
            [
                'options'  => ['foo'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'context\', ['
                    . '\'foo\''
                    . '])->setMessage(\'Test\')'
            ]
        ];
    }
}
