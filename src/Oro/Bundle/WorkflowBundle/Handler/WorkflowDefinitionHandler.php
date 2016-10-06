<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;

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

    /** @var TranslationProcessor */
    protected $translationProcessor;

    /**
     * @param WorkflowAssembler $workflowAssembler
     * @param EventDispatcherInterface $eventDispatcher
     * @param ManagerRegistry $managerRegistry
     * @param TranslationProcessor $translationProcessor
     * @param string $entityClass
     */
    public function __construct(
        WorkflowAssembler $workflowAssembler,
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $managerRegistry,
        TranslationProcessor $translationProcessor,
        $entityClass
    ) {
        $this->workflowAssembler = $workflowAssembler;
        $this->eventDispatcher = $eventDispatcher;
        $this->managerRegistry = $managerRegistry;
        $this->translationProcessor = $translationProcessor;
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
        $created = false;

        $previousDefinition = new WorkflowDefinition();
        $previousDefinition->import($workflowDefinition);

        if ($newDefinition) {
            $workflowDefinition->import($newDefinition);
        } else {
            /** @var WorkflowDefinition $existingDefinition */
            $existingDefinition = $this->getEntityRepository()->find($workflowDefinition->getName());
            if ($existingDefinition) {
                $workflowDefinition = $existingDefinition->import($workflowDefinition);
            } else {
                $created = true;
            }
        }
        $this->workflowAssembler->assemble($workflowDefinition);

        $this->eventDispatcher->dispatch(
            $created ? WorkflowEvents::WORKFLOW_BEFORE_CREATE : WorkflowEvents::WORKFLOW_BEFORE_UPDATE,
            new WorkflowChangesEvent($workflowDefinition)
        );

        $em->persist($workflowDefinition);

        $em->beginTransaction();
        try {
            $this->translationProcessor->process($workflowDefinition, $previousDefinition);

            $em->flush($workflowDefinition);
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }

        $this->eventDispatcher->dispatch(
            $created ? WorkflowEvents::WORKFLOW_AFTER_CREATE : WorkflowEvents::WORKFLOW_AFTER_UPDATE,
            new WorkflowChangesEvent($workflowDefinition)
        );
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return bool
     */
    public function deleteWorkflowDefinition(WorkflowDefinition $workflowDefinition)
    {
        if ($workflowDefinition->isSystem()) {
            return false;
        }

        $this->translationProcessor->process(null, $workflowDefinition);

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
