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
    /** @var WorkflowTransitionTriggersAssembler */
    private $assembler;

    /** @var TransitionTriggersUpdater */
    private $triggersUpdater;

    /** @var TriggersBag[] */
    private $triggerBags = [];

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
    public function updateTriggers(WorkflowChangesEvent $event)
    {
        $workflowName = $event->getDefinition()->getName();
        if (array_key_exists($workflowName, $this->triggerBags)) {
            $this->triggersUpdater->updateTriggers($this->triggerBags[$workflowName]);
        }
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function deleteTriggers(WorkflowChangesEvent $event)
    {
        $this->triggersUpdater->removeTriggers($event->getDefinition());
    }

    /**
     * @param WorkflowChangesEvent $event
     * @throws \Oro\Bundle\WorkflowBundle\Exception\AssemblerException
     */
    public function createTriggers(WorkflowChangesEvent $event)
    {
        $definition = $event->getDefinition();
        $workflowName = $definition->getName();

        $this->triggerBags[$workflowName] = new TriggersBag(
            $event->getDefinition(),
            $this->assembler->assembleTriggers($definition)
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkflowEvents::WORKFLOW_BEFORE_CREATE => 'createTriggers',
            WorkflowEvents::WORKFLOW_AFTER_CREATE => 'updateTriggers',
            WorkflowEvents::WORKFLOW_BEFORE_UPDATE => 'createTriggers',
            WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'updateTriggers',
            WorkflowEvents::WORKFLOW_AFTER_DELETE => 'deleteTriggers',
            WorkflowEvents::WORKFLOW_DEACTIVATED => 'deleteTriggers',
            WorkflowEvents::WORKFLOW_ACTIVATED => [
                ['createTriggers', 10],
                ['updateTriggers', -10]
            ]
        ];
    }
}
