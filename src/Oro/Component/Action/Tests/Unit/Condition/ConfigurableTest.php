<?php

namespace Oro\Component\Action\Tests\Unit\Condition;

use Doctrine\Common\Collections\Collection;
use Oro\Component\Action\Condition\Configurable;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ExpressionAssembler;
use Oro\Component\ConfigExpression\ExpressionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigurableTest extends TestCase
{
    private ExpressionAssembler&MockObject $assembler;
    private Configurable $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->assembler = $this->createMock(ExpressionAssembler::class);

        $this->condition = new Configurable($this->assembler);
    }

    public function testInitialize(): void
    {
        $this->assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize([])
        );
    }

    public function testEvaluate(): void
    {
        $options = [];
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass(Collection::class);
        $realCondition = $this->createMock(ExpressionInterface::class);
        $realCondition->expects($this->exactly(2))
            ->method('evaluate')
            ->with($context, $errors)
            ->willReturn(true);
        $this->assembler->expects($this->once())
            ->method('assemble')
            ->with($options)
            ->willReturn($realCondition);
        $this->condition->initialize($options);
        $this->assertTrue($this->condition->evaluate($context, $errors));
        $this->assertTrue($this->condition->evaluate($context, $errors));
    }
}
