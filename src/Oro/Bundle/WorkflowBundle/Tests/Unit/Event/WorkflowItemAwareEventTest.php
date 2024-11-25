<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;
use PHPUnit\Framework\TestCase;

class WorkflowItemAwareEventTest extends TestCase
{
    public function testGetWorkflowItem(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = $this->getMockForAbstractClass(WorkflowItemAwareEvent::class, [$workflowItem]);

        $this->assertSame($workflowItem, $event->getWorkflowItem());
    }

    public function testSetWorkflowItem(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = $this->getMockForAbstractClass(WorkflowItemAwareEvent::class, [$workflowItem]);

        $newWorkflowItem = $this->createMock(WorkflowItem::class);
        $event->setWorkflowItem($newWorkflowItem);

        $this->assertSame($newWorkflowItem, $event->getWorkflowItem());
    }
}
