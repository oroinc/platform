<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;
use Oro\Component\Action\Action\ActionInterface;

class FormInitListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormInitListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new FormInitListener();
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->listener->getSubscribedEvents();
        $this->assertCount(0, $events);
    }

    public function testExecuteInitAction()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->listener->initialize($workflowItem, $action);
        $this->listener->executeInitAction();
    }
}
