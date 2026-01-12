<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Component\Action\Exception\AssemblerException;

/**
 * Listens to workflow definition changes to validate workflow configurations.
 *
 * This listener responds to workflow creation and update events by assembling and validating
 * the workflow definition, ensuring that only valid configurations are persisted.
 */
class WorkflowDefinitionValidateListener
{
    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    public function __construct(WorkflowAssembler $workflowAssembler)
    {
        $this->workflowAssembler = $workflowAssembler;
    }

    public function onUpdateWorkflowDefinition(WorkflowChangesEvent $event)
    {
        $this->tryAssemble($event->getDefinition());
    }

    public function onCreateWorkflowDefinition(WorkflowChangesEvent $event)
    {
        $this->tryAssemble($event->getDefinition());
    }

    protected function tryAssemble(WorkflowDefinition $workflowDefinition)
    {
        try {
            $this->workflowAssembler->assemble($workflowDefinition, true);
        } catch (AssemblerException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }
}
