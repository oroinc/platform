<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdater;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggersBag;
use Oro\Bundle\WorkflowBundle\Model\WorkflowTransitionTriggersAssembler;

class WorkflowTransitionTriggersListener implements EventSubscriberInterface
{
    /**
     * @var WorkflowTransitionTriggersAssembler
     */
    private $assembler;

    /**
     * @var TransitionTriggersUpdater
     */
    private $triggersUpdater;

    /**
     * @param WorkflowTransitionTriggersAssembler $assembler
     * @param TransitionTriggersUpdater $triggersUpdater
     */
    public function __construct(
        WorkflowTransitionTriggersAssembler $assembler,
        TransitionTriggersUpdater $triggersUpdater
    ) {
        $this->assembler = $assembler;
        $this->triggersUpdater = $triggersUpdater;
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function triggersUpdate(WorkflowChangesEvent $event)
    {
        $triggers = $this->assembler->assembleTriggers($event->getDefinition());

        $triggersBag = new TriggersBag($event->getDefinition(), $triggers);

        $this->triggersUpdater->updateTriggers($triggersBag);
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function triggersDelete(WorkflowChangesEvent $event)
    {
        $this->triggersUpdater->removeTriggers($event->getDefinition());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkflowEvents::WORKFLOW_AFTER_CREATE => 'triggersUpdate',
            WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'triggersUpdate',
            WorkflowEvents::WORKFLOW_AFTER_DELETE => 'triggersDelete',
            WorkflowEvents::WORKFLOW_DEACTIVATED => 'triggersDelete',
            WorkflowEvents::WORKFLOW_ACTIVATED => 'triggersUpdate'
        ];
    }
}
