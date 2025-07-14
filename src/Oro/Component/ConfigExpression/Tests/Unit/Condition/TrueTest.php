<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\Condition\TrueCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TrueTest extends TestCase
{
    private Condition\TrueCondition $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new TrueCondition();
    }

    public function testEvaluate(): void
    {
        $this->assertTrue($this->condition->evaluate('anything'));
    }

    public function testInitializeSuccess(): void
    {
        $this->assertSame($this->condition, $this->condition->initialize([]));
    }

    public function testInitializeFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options are prohibited');

        $this->condition->initialize(['anything']);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(?string $message, array $expected): void
    {
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
                'message'  => null,
                'expected' => [
                    '@true' => null
                ]
            ],
            [
                'message'  => 'Test',
                'expected' => [
                    '@true' => [
                        'message' => 'Test'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(?string $message, string $expected): void
    {
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
                'message'  => null,
                'expected' => '$factory->create(\'true\', [])'
            ],
            [
                'message'  => 'Test',
                'expected' => '$factory->create(\'true\', [])->setMessage(\'Test\')'
            ]
        ];
    }
}
