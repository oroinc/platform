<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;

class WorkflowDefinitionScopeListener
{
    /** @var WorkflowScopeManager */
    protected $workflowScopeManager;

    /**
     * @param WorkflowScopeManager $workflowScopeManager
     */
    public function __construct(WorkflowScopeManager $workflowScopeManager)
    {
        $this->workflowScopeManager = $workflowScopeManager;
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function onActivationWorkflowDefinition(WorkflowChangesEvent $event)
    {
        $this->workflowScopeManager->updateScopes($event->getDefinition());
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function onDeactivationWorkflowDefinition(WorkflowChangesEvent $event)
    {
        $this->workflowScopeManager->updateScopes($event->getDefinition(), true);
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function onCreateWorkflowDefinition(WorkflowChangesEvent $event)
    {
        $definition = $event->getDefinition();

        if ($definition->getScopesConfig()) {
            $this->workflowScopeManager->updateScopes($event->getDefinition(), !$definition->isActive());
        }
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function onUpdateWorkflowDefinition(WorkflowChangesEvent $event)
    {
        $definition = $event->getDefinition();
        $originalDefinition = $event->getOriginalDefinition();

        if ($definition->getScopesConfig() !== $originalDefinition->getScopesConfig()) {
            $this->workflowScopeManager->updateScopes($event->getDefinition(), !$definition->isActive());
        }
    }
}
