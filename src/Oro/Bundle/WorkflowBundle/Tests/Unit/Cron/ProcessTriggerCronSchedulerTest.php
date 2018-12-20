<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cron;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Cron\ProcessTriggerCronScheduler;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerCronSchedulerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DeferredScheduler|\PHPUnit\Framework\MockObject\MockObject */
    protected $deferredScheduler;

    /** @var ProcessTriggerCronScheduler */
    protected $processCronScheduler;

    protected function setUp()
    {
        $this->deferredScheduler = $this->getMockBuilder(DeferredScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->processCronScheduler = new ProcessTriggerCronScheduler($this->deferredScheduler);
    }

    public function testAdd()
    {
        $cronExpression = '* * * * *';

        $processDefinitionMock = $this->getMockBuilder(ProcessDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processDefinitionMock->expects($this->once())->method('getName')->willReturn('process-definition-name');

        /** @var ProcessTrigger|\PHPUnit\Framework\MockObject\MockObject $trigger * */
        $trigger = $this->createMock(ProcessTrigger::class);
        $trigger->expects($this->any())->method('getCron')->willReturn($cronExpression);

        //create arguments
        $trigger->expects($this->once())->method('getDefinition')->willReturn($processDefinitionMock);
        $trigger->expects($this->once())->method('getId')->willReturn(100500);

        $arguments = ['--name=process-definition-name', '--id=100500'];

        $this->deferredScheduler->expects($this->once())
            ->method('addSchedule')
            ->with(HandleProcessTriggerCommand::NAME, $arguments, $cronExpression);

        $this->processCronScheduler->add($trigger);
    }

    public function testRemoveSchedule()
    {
        /** @var ProcessTrigger|\PHPUnit\Framework\MockObject\MockObject $mockTrigger */
        $mockTrigger = $this->createMock(ProcessTrigger::class);

        $mockProcessDefinition = $this->createMock(ProcessDefinition::class);
        $mockProcessDefinition->expects($this->once())->method('getName')->willReturn('process_name');

        $mockTrigger->expects($this->exactly(2))->method('getCron')->willReturn('* * * * *');
        $mockTrigger->expects($this->exactly(1))->method('getId')->willReturn(42);
        $mockTrigger->expects($this->once())->method('getDefinition')->willReturn($mockProcessDefinition);

        $this->deferredScheduler->expects($this->once())
            ->method('removeSchedule')
            ->with('oro:process:handle-trigger', ['--name=process_name', '--id=42'], '* * * * *');

        $this->processCronScheduler->removeSchedule($mockTrigger);
    }

    public function testRemoveException()
    {
        /** @var ProcessTrigger|\PHPUnit\Framework\MockObject\MockObject $mockTrigger */
        $mockTrigger = $this->createMock(ProcessTrigger::class);
        $mockTrigger->expects($this->exactly(1))->method('getCron')->willReturn(null);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Oro\Bundle\WorkflowBundle\Cron\ProcessTriggerCronScheduler supports only cron schedule triggers.'
        );

        $this->processCronScheduler->removeSchedule($mockTrigger);
    }

    public function testAddException()
    {
        /** @var ProcessTrigger|\PHPUnit\Framework\MockObject\MockObject $trigger * */
        $trigger = $this->createMock(ProcessTrigger::class);
        $trigger->expects($this->once())->method('getCron')->willReturn(null);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Oro\Bundle\WorkflowBundle\Cron\ProcessTriggerCronScheduler supports only cron schedule triggers.'
        );

        $this->processCronScheduler->add($trigger);
    }
}
