<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;

class FalseTest extends \PHPUnit_Framework_TestCase
{
    /** @var Condition\FalseCondition */
    protected $condition;

    protected function setUp()
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

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options are prohibited
     */
    public function testInitializeFails()
    {
        $this->condition->initialize(['anything']);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($message, $expected)
    {
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
    public function testCompile($message, $expected)
    {
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
