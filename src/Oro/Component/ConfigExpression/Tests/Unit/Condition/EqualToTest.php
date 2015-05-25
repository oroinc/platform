<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class EqualToTest extends \PHPUnit_Framework_TestCase
{
    /** @var Condition\EqualTo */
    protected $condition;

    protected function setUp()
    {
        $this->condition = new Condition\EqualTo();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, $context, $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function evaluateDataProvider()
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
    public function testToArray($options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider()
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
    public function testCompile($options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider()
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
