<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractComposite;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Exception\UnexpectedTypeException;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Testing\ReflectionUtil;

class AbstractCompositeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractComposite */
    private $condition;

    protected function setUp(): void
    {
        $this->condition = new class() extends AbstractComposite {
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

        self::assertSame($this->condition, $this->condition->initialize($operands));
        self::assertEquals($operands, ReflectionUtil::getPropertyValue($this->condition, 'operands'));
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
        $this->expectExceptionMessage(sprintf(
            'Invalid type of option "0". Expected "%s", "string" given.',
            ExpressionInterface::class
        ));

        $this->condition->initialize(['anything']);
    }

    public function testInitializeFailsWithWrongInstanceElement()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid type of option "0". Expected "%s", "stdClass" given.',
            ExpressionInterface::class
        ));

        $this->condition->initialize([new \stdClass]);
    }
}
