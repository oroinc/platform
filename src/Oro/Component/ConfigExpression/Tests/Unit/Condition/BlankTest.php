<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class BlankTest extends \PHPUnit\Framework\TestCase
{
    /** @var Condition\Blank */
    protected $condition;

    protected function setUp()
    {
        $this->condition = new Condition\Blank();
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
            'not_empty_string' => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 'bar'],
                'expectedResult' => false
            ],
            'not_empty_zero'   => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 0],
                'expectedResult' => false
            ],
            'empty_string'     => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => ''],
                'expectedResult' => true
            ],
            'empty_null'       => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => null],
                'expectedResult' => true
            ],
            'no_value'         => [
                'options'        => [new PropertyPath('foo')],
                'context'        => [],
                'expectedResult' => true
            ],
            'empty_array'         => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => []],
                'expectedResult' => false
            ]
        ];
    }

    public function testAddError()
    {
        $context = ['foo' => 'bar'];
        $options = [new PropertyPath('foo')];

        $this->condition->initialize($options);
        $message = 'Error message.';
        $this->condition->setMessage($message);

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            ['message' => $message, 'parameters' => ['{{ value }}' => 'bar']],
            $errors->get(0)
        );
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
                'options'  => ['value'],
                'message'  => null,
                'expected' => [
                    '@empty' => [
                        'parameters' => [
                            'value'
                        ]
                    ]
                ]
            ],
            [
                'options'  => ['value'],
                'message'  => 'Test',
                'expected' => [
                    '@empty' => [
                        'message'    => 'Test',
                        'parameters' => [
                            'value'
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
                'options'  => ['value'],
                'message'  => null,
                'expected' => '$factory->create(\'empty\', [\'value\'])'
            ],
            [
                'options'  => ['value'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'empty\', [\'value\'])->setMessage(\'Test\')'
            ],
            [
                'options'  => [new PropertyPath('foo[bar].baz')],
                'message'  => null,
                'expected' => '$factory->create(\'empty\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath('
                    . '\'foo[bar].baz\', [\'foo\', \'bar\', \'baz\'], [false, true, false])'
                    . '])'
            ]
        ];
    }
}
