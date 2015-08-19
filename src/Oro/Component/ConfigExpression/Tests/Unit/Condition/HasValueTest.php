<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;

class HasValueTest extends \PHPUnit_Framework_TestCase
{
    /** @var Condition\HasValue */
    protected $condition;

    protected function setUp()
    {
        $this->condition = new Condition\HasValue();
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

    public function evaluateDataProvider()
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

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 0 given.
     */
    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->condition->initialize([]);
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
