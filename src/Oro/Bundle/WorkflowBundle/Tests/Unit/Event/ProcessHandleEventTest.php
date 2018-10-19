<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;

class ProcessHandleEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $processTrigger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $processData;

    /**
     * @var ProcessHandleEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->processTrigger = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processData = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessData')
            ->disableOriginalConstructor()
            ->getMock();

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
