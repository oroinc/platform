<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractComposite;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Exception\UnexpectedTypeException;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class AbstractCompositeTest extends TestCase
{
    private AbstractComposite $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new class () extends AbstractComposite {
            protected function isConditionAllowed($context)
            {
            }

            public function getName()
            {
            }
        };
    }

    public function testInitializeSuccess(): void
    {
        $operands = [$this->createMock(ExpressionInterface::class)];

        self::assertSame($this->condition, $this->condition->initialize($operands));
        self::assertEquals($operands, ReflectionUtil::getPropertyValue($this->condition, 'operands'));
    }

    public function testInitializeFailsWithEmptyElements(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have at least one element');

        $this->condition->initialize([]);
    }

    public function testInitializeFailsWithScalarElement(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid type of option "0". Expected "%s", "string" given.',
            ExpressionInterface::class
        ));

        $this->condition->initialize(['anything']);
    }

    public function testInitializeFailsWithWrongInstanceElement(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid type of option "0". Expected "%s", "stdClass" given.',
            ExpressionInterface::class
        ));

        $this->condition->initialize([new \stdClass()]);
    }
}
