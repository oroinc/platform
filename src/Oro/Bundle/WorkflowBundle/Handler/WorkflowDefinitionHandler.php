<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Handler\Helper\WorkflowDefinitionCloner;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

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
     * @param WorkflowDefinition $existingDefinition
     * @param WorkflowDefinition|null $newDefinition
     * @throws \Exception
     */
    public function updateWorkflowDefinition(
        WorkflowDefinition $existingDefinition,
        WorkflowDefinition $newDefinition
    ) {
        $em = $this->getEntityManager();

        $previous = WorkflowDefinitionCloner::cloneDefinition($existingDefinition);

        $existingDefinition->import($newDefinition);

        $workflow = $this->workflowAssembler->assemble($existingDefinition);
        $this->setSteps($existingDefinition, $workflow);

        $this->eventDispatcher->dispatch(
            WorkflowEvents::WORKFLOW_BEFORE_UPDATE,
            new WorkflowChangesEvent($existingDefinition, $previous)
        );

        $em->beginTransaction();
        try {
            $em->flush();
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }

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
        $em = $this->getEntityManager();

        $workflow = $this->workflowAssembler->assemble($workflowDefinition);
        $this->setSteps($workflowDefinition, $workflow);

        $this->eventDispatcher->dispatch(
            WorkflowEvents::WORKFLOW_BEFORE_CREATE,
            new WorkflowChangesEvent($workflowDefinition)
        );

        $em->persist($workflowDefinition);

        $em->beginTransaction();
        try {
            $em->flush();
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }

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
     * @param Workflow $workflow
     */
    protected function setSteps(WorkflowDefinition $workflowDefinition, Workflow $workflow)
    {
        $workflowSteps = array();
        foreach ($workflow->getStepManager()->getSteps() as $step) {
            $workflowStep = new WorkflowStep();
            $workflowStep
                ->setName($step->getName())
                ->setLabel($step->getLabel())
                ->setStepOrder($step->getOrder())
                ->setFinal($step->isFinal());

            $workflowSteps[] = $workflowStep;
        }

        $workflowDefinition->setSteps($workflowSteps);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass($this->entityClass);
    }
}
