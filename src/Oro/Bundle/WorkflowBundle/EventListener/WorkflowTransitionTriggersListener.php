<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Exception\AssemblerException;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdater;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggersBag;
use Oro\Bundle\WorkflowBundle\Model\WorkflowTransitionTriggersAssembler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WorkflowTransitionTriggersListener implements EventSubscriberInterface
{
    /** @var WorkflowTransitionTriggersAssembler */
    private $assembler;

    /** @var TransitionTriggersUpdater */
    private $triggersUpdater;

    /** @var TriggersBag[] */
    private $triggerBags = [];

    public function __construct(
        WorkflowTransitionTriggersAssembler $assembler,
        TransitionTriggersUpdater $triggersUpdater
    ) {
        $this->assembler = $assembler;
        $this->triggersUpdater = $triggersUpdater;
    }

    public function updateTriggers(WorkflowChangesEvent $event)
    {
        $workflowName = $event->getDefinition()->getName();
        if (array_key_exists($workflowName, $this->triggerBags)) {
            $this->triggersUpdater->updateTriggers($this->triggerBags[$workflowName]);

            unset($this->triggerBags[$workflowName]);
        }
    }

    public function deleteTriggers(WorkflowChangesEvent $event)
    {
        $this->triggersUpdater->removeTriggers($event->getDefinition());
    }

    /**
     * @throws AssemblerException
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

    #[\Override]
    public static function getSubscribedEvents(): array
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
