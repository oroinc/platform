<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionAssembler;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable;
use Oro\Component\ConfigExpression\ExpressionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigurableTest extends TestCase
{
    private array $testConfiguration = ['key' => 'value'];
    private array $testContext = [1, 2, 3];

    private ActionAssembler&MockObject $assembler;
    private ActionInterface&MockObject $dummyAction;
    private Configurable $configurableAction;

    #[\Override]
    protected function setUp(): void
    {
        $this->assembler = $this->getMockBuilder(ActionAssembler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['assemble'])
            ->getMock();
        $this->dummyAction = $this->createMock(ActionInterface::class);

        $this->configurableAction = new Configurable($this->assembler);
    }

    public function testWithoutInitializeUsedEmptyConfigurationForAssembling(): void
    {
        $this->assembler->expects(self::once())
            ->method('assemble')
            ->with([])
            ->willReturn($this->dummyAction);

        $this->configurableAction->execute($this->testContext);
    }
    public function testInitializeSetsConfigurationUsedForAssembling(): void
    {
        $this->assembler->expects(self::once())
            ->method('assemble')
            ->with($this->testConfiguration)
            ->willReturn($this->dummyAction);

        $this->configurableAction->initialize($this->testConfiguration);
        $this->configurableAction->execute($this->testContext);
    }

    public function testExecute(): void
    {
        $this->dummyAction->expects(self::exactly(2))
            ->method('execute')
            ->with($this->testContext);

        $condition = $this->createMock(ExpressionInterface::class);
        $condition->expects(self::never())
            ->method('evaluate');

        $this->assembler->expects(self::once())
            ->method('assemble')
            ->with($this->testConfiguration)
            ->willReturn($this->dummyAction);

        $this->configurableAction->initialize($this->testConfiguration);
        $this->configurableAction->setCondition($condition);

        // run twice to test cached action
        $this->configurableAction->execute($this->testContext);
        $this->configurableAction->execute($this->testContext);
    }
}
