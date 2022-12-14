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
    private $deferredScheduler;

    /** @var ProcessTriggerCronScheduler */
    private $processCronScheduler;

    protected function setUp(): void
    {
        $this->deferredScheduler = $this->createMock(DeferredScheduler::class);

        $this->processCronScheduler = new ProcessTriggerCronScheduler($this->deferredScheduler);
    }

    public function testAdd()
    {
        $cronExpression = '* * * * *';

        $processDefinition = $this->createMock(ProcessDefinition::class);
        $processDefinition->expects($this->once())
            ->method('getName')
            ->willReturn('process-definition-name');

        $trigger = $this->createMock(ProcessTrigger::class);
        $trigger->expects($this->any())
            ->method('getCron')
            ->willReturn($cronExpression);

        //create arguments
        $trigger->expects($this->once())
            ->method('getDefinition')
            ->willReturn($processDefinition);
        $trigger->expects($this->once())
            ->method('getId')
            ->willReturn(100500);

        $arguments = ['--name=process-definition-name', '--id=100500'];

        $this->deferredScheduler->expects($this->once())
            ->method('addSchedule')
            ->with(HandleProcessTriggerCommand::getDefaultName(), $arguments, $cronExpression);

        $this->processCronScheduler->add($trigger);
    }

    public function testRemoveSchedule()
    {
        $processDefinition = $this->createMock(ProcessDefinition::class);
        $processDefinition->expects($this->once())
            ->method('getName')
            ->willReturn('process_name');

        $trigger = $this->createMock(ProcessTrigger::class);
        $trigger->expects($this->exactly(2))
            ->method('getCron')
            ->willReturn('* * * * *');
        $trigger->expects($this->once())
            ->method('getId')
            ->willReturn(42);
        $trigger->expects($this->once())
            ->method('getDefinition')
            ->willReturn($processDefinition);

        $this->deferredScheduler->expects($this->once())
            ->method('removeSchedule')
            ->with('oro:process:handle-trigger', ['--name=process_name', '--id=42'], '* * * * *');

        $this->processCronScheduler->removeSchedule($trigger);
    }

    public function testRemoveException()
    {
        $trigger = $this->createMock(ProcessTrigger::class);
        $trigger->expects($this->once())
            ->method('getCron')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Oro\Bundle\WorkflowBundle\Cron\ProcessTriggerCronScheduler supports only cron schedule triggers.'
        );

        $this->processCronScheduler->removeSchedule($trigger);
    }

    public function testAddException()
    {
        $trigger = $this->createMock(ProcessTrigger::class);
        $trigger->expects($this->once())
            ->method('getCron')
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Oro\Bundle\WorkflowBundle\Cron\ProcessTriggerCronScheduler supports only cron schedule triggers.'
        );

        $this->processCronScheduler->add($trigger);
    }
}
