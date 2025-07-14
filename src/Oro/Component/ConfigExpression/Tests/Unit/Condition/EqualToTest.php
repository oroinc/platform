<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\Condition\EqualTo;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class EqualToTest extends TestCase
{
    private Condition\EqualTo $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new EqualTo();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, $context, $expectedResult): void
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function evaluateDataProvider(): array
    {
        $options = ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')];

        return [
            'scalars_equal'     => [
                'options'        => $options,
                'context'        => ['foo' => 'value', 'bar' => 'value'],
                'expectedResult' => true
            ],
            'scalars_not_equal' => [
                'options'        => $options,
                'context'        => ['foo' => 'fooValue', 'bar' => 'barValue'],
                'expectedResult' => false
            ],
            'objects_equal'     => [
                'options'        => $options,
                'context'        => [
                    'foo' => $left = $this->createObject(),
                    'bar' => $right = $this->createObject()
                ],
                'expectedResult' => true
            ],
            'objects_not_equal' => [
                'options'        => $options,
                'context'        => [
                    'foo' => $left = $this->createObject(['foo' => 'bar']),
                    'bar' => $right = $this->createObject(['foo' => 'baz']),
                ],
                'expectedResult' => false
            ]
        ];
    }

    /**
     * @param array $data
     *
     * @return ItemStub
     */
    protected function createObject(array $data = [])
    {
        return new ItemStub($data);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($options, $message, $expected): void
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
                'options'  => ['left', 'right'],
                'message'  => null,
                'expected' => [
                    '@eq' => [
                        'parameters' => [
                            'left',
                            'right'
                        ]
                    ]
                ]
            ],
            [
                'options'  => ['left', 'right'],
                'message'  => 'Test',
                'expected' => [
                    '@eq' => [
                        'message'    => 'Test',
                        'parameters' => [
                            'left',
                            'right'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile($options, $message, $expected): void
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
                'options'  => [new PropertyPath('foo'), 123],
                'message'  => null,
                'expected' => '$factory->create(\'eq\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false])'
                    . ', 123])'
            ],
            [
                'options'  => [new PropertyPath('foo'), true],
                'message'  => null,
                'expected' => '$factory->create(\'eq\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false])'
                    . ', true])'
            ],
            [
                'options'  => [new PropertyPath('foo'), 'test'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'eq\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false])'
                    . ', \'test\'])->setMessage(\'Test\')'
            ]
        ];
    }
}
