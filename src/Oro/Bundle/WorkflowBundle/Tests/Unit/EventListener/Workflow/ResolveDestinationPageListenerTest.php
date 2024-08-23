<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Workflow;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\EventListener\Workflow\ResolveDestinationPageListener;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResolveDestinationPageListenerTest extends TestCase
{
    private ActionFactoryInterface|MockObject $actionFactory;
    private ResolveDestinationPageListener $listener;

    protected function setUp(): void
    {
        $this->actionFactory = $this->createMock(ActionFactoryInterface::class);
        $this->listener = new ResolveDestinationPageListener($this->actionFactory);
    }

    public function testOnTransitionNotPageDisplayType(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getDisplayType')->willReturn('other');

        $event = new TransitionEvent($this->createMock(WorkflowItem::class), $transition);

        $this->actionFactory->expects($this->never())
            ->method('create');

        $this->listener->onTransition($event);
    }

    public function testOnTransitionPageDisplayType(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getDisplayType')->willReturn(WorkflowConfiguration::TRANSITION_DISPLAY_TYPE_PAGE);
        $transition->method('getDestinationPage')->willReturn('some_destination');

        $workflowItem = $this->createMock(WorkflowItem::class);

        $event = new TransitionEvent($workflowItem, $transition);

        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->actionFactory->expects($this->once())
            ->method('create')
            ->with('resolve_destination_page', ['destination' => 'some_destination'])
            ->willReturn($action);

        $this->listener->onTransition($event);
    }
}
