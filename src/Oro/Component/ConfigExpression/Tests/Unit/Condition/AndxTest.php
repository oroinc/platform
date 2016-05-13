<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\ConfigExpression\Condition;

class AndxTest extends \PHPUnit_Framework_TestCase
{
    /** @var Condition\Andx */
    protected $condition;

    protected function setUp()
    {
        $this->condition = new Condition\Andx();
    }

    public function testEvaluateTrue()
    {
        $this->assertSame(
            $this->condition,
            $this->condition->initialize(
                [
                    new Condition\TrueCondition(),
                    new Condition\TrueCondition(),
                ]
            )
        );
        $this->assertTrue($this->condition->evaluate('anything'));
    }

    public function testEvaluateFalse()
    {
        $currentConditionError = 'Current condition error';
        $nestedConditionError  = 'Nested condition error';

        $this->condition->setMessage($currentConditionError);

        $falseConditionWithError = new Condition\FalseCondition();
        $falseConditionWithError->setMessage($nestedConditionError);

        $this->condition->initialize(
            [
                new Condition\TrueCondition(),
                $falseConditionWithError,
            ]
        );
        $errors = new ArrayCollection();
        $this->assertFalse($this->condition->evaluate('anything', $errors));
        $this->assertCount(2, $errors);
        $this->assertEquals(
            ['message' => $nestedConditionError, 'parameters' => []],
            $errors->get(0)
        );
        $this->assertEquals(
            ['message' => $currentConditionError, 'parameters' => []],
            $errors->get(1)
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have at least one element.
     */
    public function testInitializeEmpty()
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
                'options'  => [new Condition\TrueCondition()],
                'message'  => null,
                'expected' => [
                    '@and' => [
                        'parameters' => [
                            ['@true' => null]
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new Condition\TrueCondition(), new Condition\FalseCondition()],
                'message'  => 'Test',
                'expected' => [
                    '@and' => [
                        'message'    => 'Test',
                        'parameters' => [
                            ['@true' => null],
                            ['@false' => null]
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
                'options'  => [new Condition\TrueCondition()],
                'message'  => null,
                'expected' => '$factory->create(\'and\', [$factory->create(\'true\', [])])'
            ],
            [
                'options'  => [new Condition\TrueCondition(), new Condition\FalseCondition()],
                'message'  => 'Test',
                'expected' => '$factory->create(\'and\', '
                    . '[$factory->create(\'true\', []), $factory->create(\'false\', [])])'
                    . '->setMessage(\'Test\')'
            ]
        ];
    }
}
