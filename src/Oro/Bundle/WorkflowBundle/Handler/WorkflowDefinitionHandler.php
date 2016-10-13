<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowDefinitionHandler
{
    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var string */
    protected $entityClass;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param WorkflowAssembler $workflowAssembler
     * @param EventDispatcherInterface $eventDispatcher
     * @param ManagerRegistry $managerRegistry
     * @param string $entityClass
     */
    public function __construct(
        WorkflowAssembler $workflowAssembler,
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $managerRegistry,
        $entityClass
    ) {
        $this->workflowAssembler = $workflowAssembler;
        $this->eventDispatcher = $eventDispatcher;
        $this->managerRegistry = $managerRegistry;
        $this->entityClass = $entityClass;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param WorkflowDefinition|null $newDefinition
     * @throws \Exception
     */
    public function updateWorkflowDefinition(
        WorkflowDefinition $workflowDefinition,
        WorkflowDefinition $newDefinition = null
    ) {
        $em = $this->getEntityManager();
        $previous = null;

        if ($newDefinition) {
            $previous = (new WorkflowDefinition())->import($workflowDefinition);
            $workflowDefinition->import($newDefinition);
        } else {
            /** @var WorkflowDefinition $existingDefinition */
            $existingDefinition = $this->getEntityRepository()->find($workflowDefinition->getName());
            if ($existingDefinition) {
                $previous = (new WorkflowDefinition())->import($existingDefinition);
                $workflowDefinition = $existingDefinition->import($workflowDefinition);
            }
        }
        $this->workflowAssembler->assemble($workflowDefinition);

        $this->eventDispatcher->dispatch(
            $previous === null ? WorkflowEvents::WORKFLOW_BEFORE_CREATE : WorkflowEvents::WORKFLOW_BEFORE_UPDATE,
            new WorkflowChangesEvent($workflowDefinition, $previous)
        );

        $em->persist($workflowDefinition);

        $em->beginTransaction();
        try {
            $em->flush($workflowDefinition);
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }

        $this->eventDispatcher->dispatch(
            $previous === null ? WorkflowEvents::WORKFLOW_AFTER_CREATE : WorkflowEvents::WORKFLOW_AFTER_UPDATE,
            new WorkflowChangesEvent($workflowDefinition, $previous)
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
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass($this->entityClass);
    }

    /**
     * @return EntityRepository
     */
    private function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository($this->entityClass);
    }
}
