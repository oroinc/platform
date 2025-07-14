<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition\FalseCondition;
use Oro\Component\ConfigExpression\Condition\Not;
use Oro\Component\ConfigExpression\Condition\TrueCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Exception\UnexpectedTypeException;
use PHPUnit\Framework\TestCase;

class NotTest extends TestCase
{
    private Not $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new Not();
    }

    public function testEvaluate(): void
    {
        $this->assertSame($this->condition, $this->condition->initialize([new TrueCondition()]));
        $this->assertFalse($this->condition->evaluate('anything'));

        $this->assertSame($this->condition, $this->condition->initialize([new FalseCondition()]));
        $this->assertTrue($this->condition->evaluate('anything'));
    }

    public function testEvaluateWithErrors(): void
    {
        $currentConditionError = 'Current condition error';
        $nestedConditionError = 'Nested condition error';

        $this->condition->setMessage($currentConditionError);

        $falseConditionWithError = new FalseCondition();
        $falseConditionWithError->setMessage($nestedConditionError);

        $errors = new ArrayCollection();
        $this->condition->initialize([$falseConditionWithError]);
        $this->assertTrue($this->condition->evaluate('anything', $errors));
        $this->assertCount(1, $errors);
        $this->assertEquals(
            ['message' => $nestedConditionError, 'parameters' => []],
            $errors->get(0)
        );

        $trueConditionWithError = new TrueCondition();
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

    public function testInitializeFailsWhenOptionNotExpressionInterface(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Invalid option type. Expected "Oro\Component\ConfigExpression\ExpressionInterface", "string" given.'
        );

        $this->condition->initialize(['anything']);
    }

    public function testInitializeFailsWhenEmptyOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 element, but 0 given.');

        $this->condition->initialize([]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, ?string $message, array $expected): void
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
                'options'  => [new TrueCondition()],
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
                'options'  => [new TrueCondition()],
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
    public function testCompile(array $options, ?string $message, string $expected): void
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
                'options'  => [new TrueCondition()],
                'message'  => null,
                'expected' => '$factory->create(\'not\', [$factory->create(\'true\', [])])'
            ],
            [
                'options'  => [new TrueCondition()],
                'message'  => 'Test',
                'expected' => '$factory->create(\'not\', [$factory->create(\'true\', [])])->setMessage(\'Test\')'
            ]
        ];
    }
}
