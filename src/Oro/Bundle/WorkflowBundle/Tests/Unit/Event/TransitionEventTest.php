<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use PHPUnit\Framework\TestCase;

class TransitionEventTest extends TestCase
{
    public function testEvent(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);
        $this->assertSame($workflowItem, $event->getWorkflowItem());
        $this->assertSame($transition, $event->getTransition());
    }
}
