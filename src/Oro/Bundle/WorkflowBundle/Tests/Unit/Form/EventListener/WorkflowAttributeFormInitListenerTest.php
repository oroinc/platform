<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Event\Transition\AttributeFormInitEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionFormInitEvent;
use Oro\Bundle\WorkflowBundle\Form\EventListener\WorkflowAttributeFormInitListener;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Component\Action\Action\ActionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class WorkflowAttributeFormInitListenerTest extends TestCase
{
    private EventDispatcher|MockObject $eventDispatcher;
    private WorkflowAttributeFormInitListener $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->listener = new WorkflowAttributeFormInitListener($this->eventDispatcher);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [FormEvents::PRE_SET_DATA => 'onPreSetData'],
            WorkflowAttributeFormInitListener::getSubscribedEvents()
        );
    }

    public function testExecuteInitAction(): void
    {
        $initAction = $this->createMock(ActionInterface::class);
        $workflowItem = $this->createMock(WorkflowItem::class);

        $initAction->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->listener->executeInitActions($initAction, $workflowItem);
    }

    public function testDispatchFormInitEventsWithTransition(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);

        $transition->expects($this->once())
            ->method('getName')
            ->willReturn('transition_name');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(TransitionFormInitEvent::class),
                'transition_name'
            );

        $this->listener->dispatchFormInitEvents($workflowItem, $transition);
    }

    public function testDispatchFormInitEventsWithoutTransition(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(AttributeFormInitEvent::class)
            );

        $this->listener->dispatchFormInitEvents($workflowItem);
    }

    public function testOnPreSetData(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $initAction = $this->createMock(ActionInterface::class);
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getName')
            ->willReturn('transition_name');

        $transitionManager = $this->createMock(TransitionManager::class);
        $workflow = $this->createMock(Workflow::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $form = $this->createMock(FormInterface::class);

        $formConfig->expects($this->atLeastOnce())
            ->method('getOption')
            ->willReturnMap([
                ['workflow_item', null, $workflowItem],
                ['form_init', null, $initAction],
                ['transition_name', null, 'transition_name'],
                ['workflow', null, $workflow],
            ]);

        $initAction->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $transitionManager->expects($this->once())
            ->method('getTransition')
            ->with('transition_name')
            ->willReturn($transition);

        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $event = new PreSetDataEvent($form, null);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(TransitionFormInitEvent::class),
                'transition_name'
            );

        $this->listener->onPreSetData($event);
    }

    public function testOnPreSetDataWithNoTransition(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $initAction = $this->createMock(ActionInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $form = $this->createMock(FormInterface::class);

        $formConfig->expects($this->atLeastOnce())
            ->method('getOption')
            ->willReturnMap([
                ['workflow_item', null, $workflowItem],
                ['form_init', null, $initAction],
                ['transition_name', null, null],
            ]);

        $initAction->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $event = new PreSetDataEvent($form, null);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(AttributeFormInitEvent::class)
            );

        $this->listener->onPreSetData($event);
    }
}
