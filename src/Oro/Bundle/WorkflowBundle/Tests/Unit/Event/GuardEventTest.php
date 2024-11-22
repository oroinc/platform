<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\GuardEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use PHPUnit\Framework\TestCase;

class GuardEventTest extends TestCase
{
    public function testGuardEventConstruction(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $errors = $this->createMock(Collection::class);

        $event = new GuardEvent($workflowItem, $transition, true, $errors);

        $this->assertSame($workflowItem, $event->getWorkflowItem());
        $this->assertSame($transition, $event->getTransition());
        $this->assertTrue($event->isAllowed());
        $this->assertSame($errors, $event->getErrors());
    }

    public function testIsAllowedAndSetAllowed(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);

        $event = new GuardEvent($workflowItem, $transition, false);

        $this->assertFalse($event->isAllowed());

        $event->setAllowed(true);
        $this->assertTrue($event->isAllowed());

        $event->setAllowed(false);
        $this->assertFalse($event->isAllowed());
    }

    public function testGetErrors(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $errors = new ArrayCollection(['Error 1', 'Error 2']);

        $event = new GuardEvent($workflowItem, $transition, true, $errors);

        $this->assertSame($errors, $event->getErrors());
        $this->assertCount(2, $event->getErrors());
        $this->assertContains('Error 1', $event->getErrors());
        $this->assertContains('Error 2', $event->getErrors());
    }
}
