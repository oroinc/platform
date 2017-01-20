<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Component\Action\Exception\AssemblerException;

class WorkflowDefinitionValidateListener
{
    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /**
     * @param WorkflowAssembler $workflowAssembler
     */
    public function __construct(WorkflowAssembler $workflowAssembler)
    {
        $this->workflowAssembler = $workflowAssembler;
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function onUpdateWorkflowDefinition(WorkflowChangesEvent $event)
    {
        $this->tryAssemble($event->getDefinition());
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function onCreateWorkflowDefinition(WorkflowChangesEvent $event)
    {
        $this->tryAssemble($event->getDefinition());
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     */
    protected function tryAssemble(WorkflowDefinition $workflowDefinition)
    {
        try {
            $this->workflowAssembler->assemble($workflowDefinition, true);
        } catch (AssemblerException $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }
    }
}
