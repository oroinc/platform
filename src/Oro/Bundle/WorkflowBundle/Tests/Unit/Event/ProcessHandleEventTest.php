<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

class ProcessHandleEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessTrigger|\PHPUnit\Framework\MockObject\MockObject */
    private $processTrigger;

    /** @var ProcessData|\PHPUnit\Framework\MockObject\MockObject */
    private $processData;

    /** @var ProcessHandleEvent */
    private $event;

    protected function setUp(): void
    {
        $this->processTrigger = $this->createMock(ProcessTrigger::class);
        $this->processData = $this->createMock(ProcessData::class);

        $this->event = new ProcessHandleEvent($this->processTrigger, $this->processData);
    }

    public function testGetProcessTriggerWorks()
    {
        $this->assertSame($this->processTrigger, $this->event->getProcessTrigger());
    }

    public function testGetProcessDataWorks()
    {
        $this->assertSame($this->processData, $this->event->getProcessData());
    }
}
