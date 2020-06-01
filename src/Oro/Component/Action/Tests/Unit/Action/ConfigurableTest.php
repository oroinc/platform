<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionAssembler;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable;
use Oro\Component\ConfigExpression\ExpressionInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigurableTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    protected $testConfiguration = ['key' => 'value'];

    /** @var array */
    protected $testContext = [1, 2, 3];

    /** @var ActionAssembler|MockObject */
    private $assembler;

    /** @var ActionInterface|MockObject */
    private $dummyAction;

    /** @var Configurable */
    private $configurableAction;

    protected function setUp(): void
    {
        $this->assembler = $this->getMockBuilder(ActionAssembler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['assemble'])
            ->getMock();

        $this->dummyAction = $this->getMockBuilder(ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configurableAction = new Configurable($this->assembler);
    }

    public function testWithoutInitializeUsedEmptyConfigurationForAssembling()
    {
        $this->assembler->expects(static::once())
            ->method('assemble')
            ->with([])
            ->willReturn($this->dummyAction);

        $this->configurableAction->execute($this->testContext);
    }
    public function testInitializeSetsConfigurationUsedForAssembling()
    {
        $this->assembler->expects(static::once())
            ->method('assemble')
            ->with($this->testConfiguration)
            ->willReturn($this->dummyAction);

        $this->configurableAction->initialize($this->testConfiguration);
        $this->configurableAction->execute($this->testContext);
    }

    public function testExecute()
    {
        $this->dummyAction->expects(static::exactly(2))
            ->method('execute')
            ->with($this->testContext);

        $condition = $this->createMock(ExpressionInterface::class);
        $condition->expects(static::never())->method('evaluate');

        $this->assembler->expects(static::once())
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
