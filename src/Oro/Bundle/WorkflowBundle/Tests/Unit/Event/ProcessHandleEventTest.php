<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessHandleEventTest extends TestCase
{
    private ProcessTrigger&MockObject $processTrigger;
    private ProcessData&MockObject $processData;
    private ProcessHandleEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->processTrigger = $this->createMock(ProcessTrigger::class);
        $this->processData = $this->createMock(ProcessData::class);

        $this->event = new ProcessHandleEvent($this->processTrigger, $this->processData);
    }

    public function testGetProcessTriggerWorks(): void
    {
        $this->assertSame($this->processTrigger, $this->event->getProcessTrigger());
    }

    public function testGetProcessDataWorks(): void
    {
        $this->assertSame($this->processData, $this->event->getProcessData());
    }
}
