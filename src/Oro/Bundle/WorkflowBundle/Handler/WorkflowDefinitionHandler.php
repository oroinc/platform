<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\Helper\WorkflowDefinitionCloner;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles workflow definition changes.
 */
class WorkflowDefinitionHandler
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected ManagerRegistry $doctrine
    ) {
    }

    public function updateWorkflowDefinition(
        WorkflowDefinition $existingDefinition,
        WorkflowDefinition $newDefinition
    ): void {
        $originalDefinition = WorkflowDefinitionCloner::cloneDefinition($existingDefinition);

        WorkflowDefinitionCloner::mergeDefinition($existingDefinition, $newDefinition);

        $this->eventDispatcher->dispatch(
            new WorkflowChangesEvent($existingDefinition, $originalDefinition),
            WorkflowEvents::WORKFLOW_BEFORE_UPDATE
        );

        $this->process($existingDefinition);

        $this->eventDispatcher->dispatch(
            new WorkflowChangesEvent($existingDefinition, $originalDefinition),
            WorkflowEvents::WORKFLOW_AFTER_UPDATE
        );
    }

    public function createWorkflowDefinition(WorkflowDefinition $workflowDefinition): void
    {
        $this->eventDispatcher->dispatch(
            new WorkflowChangesEvent($workflowDefinition),
            WorkflowEvents::WORKFLOW_BEFORE_CREATE
        );

        $this->process($workflowDefinition);

        $this->eventDispatcher->dispatch(
            new WorkflowChangesEvent($workflowDefinition),
            WorkflowEvents::WORKFLOW_AFTER_CREATE
        );
    }

    public function deleteWorkflowDefinition(WorkflowDefinition $workflowDefinition): bool
    {
        if ($workflowDefinition->isSystem()) {
            return false;
        }

        $em = $this->doctrine->getManagerForClass(WorkflowDefinition::class);
        $em->remove($workflowDefinition);
        $em->flush();

        $this->eventDispatcher->dispatch(
            new WorkflowChangesEvent($workflowDefinition),
            WorkflowEvents::WORKFLOW_AFTER_DELETE
        );

        return true;
    }

    protected function process(WorkflowDefinition $workflowDefinition): void
    {
        $em = $this->doctrine->getManagerForClass(WorkflowDefinition::class);
        $em->persist($workflowDefinition);
        $em->beginTransaction();
        try {
            $em->flush();
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }
    }
}
