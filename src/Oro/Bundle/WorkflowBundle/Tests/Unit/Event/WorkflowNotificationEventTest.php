<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;

class WorkflowNotificationEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowTransitionRecord|\PHPUnit_Framework_MockObject_MockObject */
    private $transitionRecord;

    /** @var WorkflowNotificationEvent */
    private $event;

    public function setUp()
    {
        $this->transitionRecord = $this->createMock(WorkflowTransitionRecord::class);

        $this->event = new WorkflowNotificationEvent(new \stdClass(), $this->transitionRecord);
    }

    public function testGetTransitionRecord()
    {
        $this->assertSame($this->transitionRecord, $this->event->getTransitionRecord());
    }
}
