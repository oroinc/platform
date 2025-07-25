<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cron;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\WorkflowBundle\Cron\ProcessTriggerCronScheduler;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessTriggerCronSchedulerTest extends TestCase
{
    private DeferredScheduler&MockObject $deferredScheduler;
    private ProcessTriggerCronScheduler $processCronScheduler;

    #[\Override]
    protected function setUp(): void
    {
        $this->deferredScheduler = $this->createMock(DeferredScheduler::class);

        $this->processCronScheduler = new ProcessTriggerCronScheduler($this->deferredScheduler);
    }

    public function testAdd(): void
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
            ->with('oro:process:handle-trigger', $arguments, $cronExpression);

        $this->processCronScheduler->add($trigger);
    }

    public function testRemoveSchedule(): void
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

    public function testRemoveException(): void
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

    public function testAddException(): void
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
