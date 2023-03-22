<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class FalseTest extends \PHPUnit\Framework\TestCase
{
    private Condition\FalseCondition $condition;

    protected function setUp(): void
    {
        $this->condition = new Condition\FalseCondition();
    }

    public function testEvaluate()
    {
        $this->assertFalse($this->condition->evaluate('anything'));
    }

    public function testInitializeSuccess()
    {
        $this->assertSame($this->condition, $this->condition->initialize([]));
    }

    public function testInitializeFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options are prohibited');

        $this->condition->initialize(['anything']);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(?string $message, array $expected)
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
                    '@false' => null
                ]
            ],
            [
                'message'  => 'Test',
                'expected' => [
                    '@false' => [
                        'message' => 'Test'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(?string $message, string $expected)
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
                'expected' => '$factory->create(\'false\', [])'
            ],
            [
                'message'  => 'Test',
                'expected' => '$factory->create(\'false\', [])->setMessage(\'Test\')'
            ]
        ];
    }
}
