<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractComposite;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Exception\UnexpectedTypeException;
use Oro\Component\ConfigExpression\ExpressionInterface;

class AbstractCompositeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractComposite */
    protected $condition;

    protected function setUp(): void
    {
        $this->condition = new class() extends AbstractComposite {
            public function xgetOperands(): array
            {
                return $this->operands;
            }

            protected function isConditionAllowed($context)
            {
            }

            public function getName()
            {
            }
        };
    }

    public function testInitializeSuccess()
    {
        $operands = [$this->createMock(ExpressionInterface::class)];

        static::assertSame($this->condition, $this->condition->initialize($operands));
        static::assertEquals($operands, $this->condition->xgetOperands());
    }

    public function testInitializeFailsWithEmptyElements()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have at least one element');

        $this->condition->initialize([]);
    }

    public function testInitializeFailsWithScalarElement()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Invalid type of option "0". Expected "%s", "string" given.',
            ExpressionInterface::class
        ));

        $this->condition->initialize(['anything']);
    }

    public function testInitializeFailsWithWrongInstanceElement()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(\sprintf(
            'Invalid type of option "0". Expected "%s", "stdClass" given.',
            ExpressionInterface::class
        ));

        $this->condition->initialize([new \stdClass]);
    }
}
