<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class HasValueTest extends \PHPUnit\Framework\TestCase
{
    private Condition\HasValue $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new Condition\HasValue();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, array $context, bool $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function evaluateDataProvider(): array
    {
        return [
            'has_value'        => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => true
            ],
            'no_value'         => [
                'options'        => [new PropertyPath('other')],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => false
            ],
            'has_for_constant' => [
                'options'        => ['foo'],
                'context'        => [],
                'expectedResult' => true
            ]
        ];
    }

    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 element, but 0 given.');

        $this->condition->initialize([]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, ?string $message, array $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'options'  => [new PropertyPath('value')],
                'message'  => null,
                'expected' => [
                    '@has' => [
                        'parameters' => [
                            '$value'
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new PropertyPath('value')],
                'message'  => 'Test',
                'expected' => [
                    '@has' => [
                        'message'    => 'Test',
                        'parameters' => [
                            '$value'
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
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'options'  => [new PropertyPath('value')],
                'message'  => null,
                'expected' => '$factory->create(\'has\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'value\', [\'value\'], [false])'
                    . '])'
            ],
            [
                'options'  => [new PropertyPath('value')],
                'message'  => 'Test',
                'expected' => '$factory->create(\'has\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'value\', [\'value\'], [false])'
                    . '])->setMessage(\'Test\')'
            ]
        ];
    }
}
