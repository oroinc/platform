<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;

class AbstractCompositeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Condition\AbstractComposite */
    protected $condition;

    protected function setUp(): void
    {
        $this->condition = $this->getMockForAbstractClass(
            'Oro\Component\ConfigExpression\Condition\AbstractComposite'
        );
    }

    public function testInitializeSuccess()
    {
        $operands = [$this->createMock('Oro\Component\ConfigExpression\ExpressionInterface')];

        $this->assertSame($this->condition, $this->condition->initialize($operands));
        $this->assertAttributeEquals($operands, 'operands', $this->condition);
    }

    public function testInitializeFailsWithEmptyElements()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have at least one element');

        $this->condition->initialize([]);
    }

    public function testInitializeFailsWithScalarElement()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Invalid type of option "0". Expected "%s", "string" given.',
            \Oro\Component\ConfigExpression\ExpressionInterface::class
        ));

        $this->condition->initialize(['anything']);
    }

    public function testInitializeFailsWithWrongInstanceElement()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Invalid type of option "0". Expected "%s", "stdClass" given.',
            \Oro\Component\ConfigExpression\ExpressionInterface::class
        ));

        $this->condition->initialize([new \stdClass]);
    }
}
