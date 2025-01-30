<?php

namespace Oro\Bundle\WorkflowBundle\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Event\Transition\AttributeFormInitEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionFormInitEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Component\Action\Action\ActionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvents;

/**
 * Workflow attribute form initialize service.
 */
class FormInitListener implements EventSubscriberInterface
{
    public function __construct(
        private EventDispatcher $eventDispatcher
    ) {
    }

    /**
     * Executes init actions
     */
    public function executeInitAction(
        ActionInterface $initAction,
        WorkflowItem $workflowItem
    ): void {
        $initAction->execute($workflowItem);
    }

    public function dispatchFormInitEvents(WorkflowItem $workflowItem, ?Transition $transition = null): void
    {
        if ($transition) {
            $transitionFormInitEvent = new TransitionFormInitEvent($workflowItem, $transition);
            $this->eventDispatcher->dispatch($transitionFormInitEvent, $transition->getName());
        } else {
            $attributeFormInitEvent = new AttributeFormInitEvent($workflowItem);
            $this->eventDispatcher->dispatch($attributeFormInitEvent);
        }
    }

    public function onPreSetData(PreSetDataEvent $event): void
    {
        $formConfig = $event->getForm()->getConfig();
        $workflowItem = $formConfig->getOption('workflow_item');

        $initAction = $formConfig->getOption('form_init');
        if ($initAction) {
            $this->executeInitAction($initAction, $workflowItem);
        }
        $this->dispatchFormInitEvents($workflowItem, $this->getTransition($formConfig));
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'onPreSetData'];
    }

    private function getTransition(FormConfigInterface $formConfig): ?Transition
    {
        $transitionName = $formConfig->getOption('transition_name');
        if ($transitionName) {
            /** @var Workflow $workflow */
            $workflow = $formConfig->getOption('workflow');

            if ($workflow) {
                return $workflow->getTransitionManager()->getTransition($transitionName);
            }
        }

        return null;
    }
}
