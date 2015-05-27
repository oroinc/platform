<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;

class NotBlankTest extends \PHPUnit_Framework_TestCase
{
    /** @var Condition\NotBlank */
    protected $condition;

    protected function setUp()
    {
        $this->condition = new Condition\NotBlank();
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
                'expectedResult' => true
            ],
            'not_empty_zero'   => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => 0],
                'expectedResult' => true
            ],
            'empty_string'     => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => ''],
                'expectedResult' => false
            ],
            'empty_null'       => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => null],
                'expectedResult' => false
            ],
            'no_value' => [
                'options'        => [new PropertyPath('foo')],
                'context'        => [],
                'expectedResult' => false
            ]
        ];
    }

    public function testAddError()
    {
        $context = ['foo' => ''];
        $options = [new PropertyPath('foo')];

        $this->condition->initialize($options);
        $message = 'Error message.';
        $this->condition->setMessage($message);

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            ['message' => $message, 'parameters' => ['{{ value }}' => '']],
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
                    '@not_empty' => [
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
                    '@not_empty' => [
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
                'expected' => '$factory->create(\'not_empty\', [\'value\'])'
            ],
            [
                'options'  => ['value'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'not_empty\', [\'value\'])->setMessage(\'Test\')'
            ]
        ];
    }
}
