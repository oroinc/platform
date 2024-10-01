<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;
use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;
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

class FormInitListenerTest extends TestCase
{
    private EventDispatcher|MockObject $eventDispatcher;
    private FormInitListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->listener = new FormInitListener($this->eventDispatcher);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [FormEvents::PRE_SET_DATA => 'onPreSetData'],
            FormInitListener::getSubscribedEvents()
        );
    }

    public function testExecuteInitAction(): void
    {
        $initAction = $this->createMock(ActionInterface::class);
        $workflowItem = $this->createMock(WorkflowItem::class);

        $initAction->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->listener->executeInitAction($initAction, $workflowItem);
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
                $this->isInstanceOf(TransitionEvent::class),
                'transition_form_init',
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
                $this->isInstanceOf(WorkflowItemAwareEvent::class),
                'attribute_form_init',
                null
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
                $this->isInstanceOf(TransitionEvent::class),
                'transition_form_init',
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
                $this->isInstanceOf(WorkflowItemAwareEvent::class),
                'attribute_form_init',
                null
            );

        $this->listener->onPreSetData($event);
    }
}
