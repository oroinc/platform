<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class OrxTest extends \PHPUnit\Framework\TestCase
{
    private Condition\Orx $condition;

    protected function setUp(): void
    {
        $this->condition = new Condition\Orx();
    }

    public function testEvaluateTrue()
    {
        $this->assertSame(
            $this->condition,
            $this->condition->initialize(
                [
                    new Condition\TrueCondition(),
                    new Condition\FalseCondition(),
                ]
            )
        );
        $this->assertTrue($this->condition->evaluate('anything'));
    }

    public function testEvaluateFalse()
    {
        $currentConditionError = 'Current condition error';
        $nestedConditionError = 'Nested condition error';

        $this->condition->setMessage($currentConditionError);

        $falseConditionWithError = new Condition\FalseCondition();
        $falseConditionWithError->setMessage($nestedConditionError);

        $this->condition->initialize(
            [
                new Condition\FalseCondition(),
                $falseConditionWithError
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

    public function testInitializeEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have at least one element.');

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
                'options'  => [new Condition\TrueCondition()],
                'message'  => null,
                'expected' => [
                    '@or' => [
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
                    '@or' => [
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
                'options'  => [new Condition\TrueCondition()],
                'message'  => null,
                'expected' => '$factory->create(\'or\', [$factory->create(\'true\', [])])'
            ],
            [
                'options'  => [new Condition\TrueCondition(), new Condition\FalseCondition()],
                'message'  => 'Test',
                'expected' => '$factory->create(\'or\', '
                    . '[$factory->create(\'true\', []), $factory->create(\'false\', [])])'
                    . '->setMessage(\'Test\')'
            ]
        ];
    }
}
