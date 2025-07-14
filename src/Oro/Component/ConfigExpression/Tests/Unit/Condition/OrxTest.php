<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition\FalseCondition;
use Oro\Component\ConfigExpression\Condition\Orx;
use Oro\Component\ConfigExpression\Condition\TrueCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OrxTest extends TestCase
{
    private Orx $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new Orx();
    }

    public function testEvaluateTrue(): void
    {
        $this->assertSame(
            $this->condition,
            $this->condition->initialize(
                [
                    new TrueCondition(),
                    new FalseCondition(),
                ]
            )
        );
        $this->assertTrue($this->condition->evaluate('anything'));
    }

    public function testEvaluateFalse(): void
    {
        $currentConditionError = 'Current condition error';
        $nestedConditionError = 'Nested condition error';

        $this->condition->setMessage($currentConditionError);

        $falseConditionWithError = new FalseCondition();
        $falseConditionWithError->setMessage($nestedConditionError);

        $this->condition->initialize(
            [
                new FalseCondition(),
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

    public function testInitializeEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have at least one element.');

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
                    '@or' => [
                        'parameters' => [
                            ['@true' => null]
                        ]
                    ]
                ]
            ],
            [
                'options'  => [new TrueCondition(), new FalseCondition()],
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
                'expected' => '$factory->create(\'or\', [$factory->create(\'true\', [])])'
            ],
            [
                'options'  => [new TrueCondition(), new FalseCondition()],
                'message'  => 'Test',
                'expected' => '$factory->create(\'or\', '
                    . '[$factory->create(\'true\', []), $factory->create(\'false\', [])])'
                    . '->setMessage(\'Test\')'
            ]
        ];
    }
}
