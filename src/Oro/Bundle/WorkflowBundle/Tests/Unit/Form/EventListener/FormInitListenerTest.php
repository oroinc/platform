<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;
use Oro\Component\Action\Action\ActionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormEvents;

class FormInitListenerTest extends \PHPUnit\Framework\TestCase
{
    private EventDispatcher|MockObject $eventDispatcher;
    private FormInitListener $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->listener = new FormInitListener($this->eventDispatcher);
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->listener->getSubscribedEvents();
        $this->assertCount(1, $events);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
    }

    public function testExecuteInitAction()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->listener->executeInitAction($action, $workflowItem);
    }
}
