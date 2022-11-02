<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\Helper\WorkflowDefinitionCloner;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowDefinitionHandler
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, ManagerRegistry $registry)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->registry = $registry;
    }

    /**
     * @param WorkflowDefinition $existingDefinition
     * @param WorkflowDefinition|null $newDefinition
     * @throws \Exception
     */
    public function updateWorkflowDefinition(WorkflowDefinition $existingDefinition, WorkflowDefinition $newDefinition)
    {
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

    /**
     * @throws \Exception
     */
    public function createWorkflowDefinition(WorkflowDefinition $workflowDefinition)
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

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return bool
     * @throws OptimisticLockException
     * @throws ORMInvalidArgumentException
     */
    public function deleteWorkflowDefinition(WorkflowDefinition $workflowDefinition)
    {
        if ($workflowDefinition->isSystem()) {
            return false;
        }

        $em = $this->getEntityManager();
        $em->remove($workflowDefinition);
        $em->flush();

        $this->eventDispatcher->dispatch(
            new WorkflowChangesEvent($workflowDefinition),
            WorkflowEvents::WORKFLOW_AFTER_DELETE
        );

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function process(WorkflowDefinition $workflowDefinition)
    {
        $em = $this->getEntityManager();
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

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->registry->getManagerForClass(WorkflowDefinition::class);
    }
}
