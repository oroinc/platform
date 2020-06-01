<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition;

class NotTest extends \PHPUnit\Framework\TestCase
{
    /** @var Condition\Not */
    protected $condition;

    protected function setUp(): void
    {
        $this->condition = new Condition\Not();
    }

    public function testEvaluate()
    {
        $this->assertSame($this->condition, $this->condition->initialize([new Condition\TrueCondition()]));
        $this->assertFalse($this->condition->evaluate('anything'));

        $this->assertSame($this->condition, $this->condition->initialize([new Condition\FalseCondition()]));
        $this->assertTrue($this->condition->evaluate('anything'));
    }

    public function testEvaluateWithErrors()
    {
        $currentConditionError = 'Current condition error';
        $nestedConditionError  = 'Nested condition error';

        $this->condition->setMessage($currentConditionError);

        $falseConditionWithError = new Condition\FalseCondition();
        $falseConditionWithError->setMessage($nestedConditionError);

        $errors = new ArrayCollection();
        $this->condition->initialize([$falseConditionWithError]);
        $this->assertTrue($this->condition->evaluate('anything', $errors));
        $this->assertCount(1, $errors);
        $this->assertEquals(
            ['message' => $nestedConditionError, 'parameters' => []],
            $errors->get(0)
        );

        $trueConditionWithError = new Condition\TrueCondition();
        $trueConditionWithError->setMessage($nestedConditionError);

        $errors = new ArrayCollection();
        $this->condition->initialize([$trueConditionWithError]);
        $this->assertFalse($this->condition->evaluate('anything', $errors));
        $this->assertCount(1, $errors);
        $this->assertEquals(
            ['message' => $currentConditionError, 'parameters' => []],
            $errors->get(0)
        );
    }

    public function testInitializeFailsWhenOptionNotExpressionInterface()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Invalid option type. Expected "Oro\Component\ConfigExpression\ExpressionInterface", "string" given.'
        );

        $this->condition->initialize(['anything']);
    }

    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 element, but 0 given.');

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
                    '@not' => [
                        'parameters' => [
                            ['@true' => null]
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new Condition\TrueCondition()],
                'message'  => 'Test',
                'expected' => [
                    '@not' => [
                        'message'    => 'Test',
                        'parameters' => [
                            ['@true'  => null]
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
                'expected' => '$factory->create(\'not\', [$factory->create(\'true\', [])])'
            ],
            [
                'options'  => [new Condition\TrueCondition()],
                'message'  => 'Test',
                'expected' => '$factory->create(\'not\', [$factory->create(\'true\', [])])->setMessage(\'Test\')'
            ]
        ];
    }
}
