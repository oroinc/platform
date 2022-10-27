<?php

namespace Oro\Component\Action\Tests\Unit\Condition;

use Doctrine\Common\Collections\Collection;
use Oro\Component\Action\Condition\Configurable;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ExpressionAssembler;
use Oro\Component\ConfigExpression\ExpressionInterface;

class ConfigurableTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $assembler;

    /** @var Configurable */
    private $condition;

    protected function setUp(): void
    {
        $this->assembler = $this->createMock(ExpressionAssembler::class);

        $this->condition = new Configurable($this->assembler);
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize([])
        );
    }

    public function testEvaluate()
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
