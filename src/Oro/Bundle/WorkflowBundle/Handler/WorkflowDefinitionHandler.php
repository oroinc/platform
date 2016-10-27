<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\Helper\WorkflowDefinitionCloner;

class WorkflowDefinitionHandler
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ManagerRegistry $registry
     */
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
        $previous = WorkflowDefinitionCloner::cloneDefinition($existingDefinition);

        WorkflowDefinitionCloner::mergeDefinition($existingDefinition, $newDefinition);

        $this->eventDispatcher->dispatch(
            WorkflowEvents::WORKFLOW_BEFORE_UPDATE,
            new WorkflowChangesEvent($existingDefinition, $previous)
        );

        $this->process($existingDefinition);

        $this->eventDispatcher->dispatch(
            WorkflowEvents::WORKFLOW_AFTER_UPDATE,
            new WorkflowChangesEvent($existingDefinition, $previous)
        );
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @throws \Exception
     */
    public function createWorkflowDefinition(WorkflowDefinition $workflowDefinition)
    {
        $workflowDefinition = WorkflowDefinitionCloner::cloneDefinition($workflowDefinition);

        $this->eventDispatcher->dispatch(
            WorkflowEvents::WORKFLOW_BEFORE_CREATE,
            new WorkflowChangesEvent($workflowDefinition)
        );

        $this->process($workflowDefinition);

        $this->eventDispatcher->dispatch(
            WorkflowEvents::WORKFLOW_AFTER_CREATE,
            new WorkflowChangesEvent($workflowDefinition)
        );
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
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
            WorkflowEvents::WORKFLOW_AFTER_DELETE,
            new WorkflowChangesEvent($workflowDefinition)
        );

        return true;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
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
