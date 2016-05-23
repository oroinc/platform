<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

class WorkflowDefinitionHandler
{
    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var ManagerRegistry  */
    protected $managerRegistry;

    /** @var string */
    protected $entityClass;

    /**
     * @param WorkflowAssembler $workflowAssembler
     * @param ManagerRegistry $managerRegistry
     * @param string $entityClass
     */
    public function __construct(
        WorkflowAssembler $workflowAssembler,
        ManagerRegistry $managerRegistry,
        $entityClass
    ) {
        $this->workflowAssembler = $workflowAssembler;
        $this->managerRegistry = $managerRegistry;
        $this->entityClass = $entityClass;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param WorkflowDefinition|null $newDefinition
     */
    public function updateWorkflowDefinition(
        WorkflowDefinition $workflowDefinition,
        WorkflowDefinition $newDefinition = null
    ) {
        if ($newDefinition) {
            $workflowDefinition->import($newDefinition);
        } else {
            /** @var WorkflowDefinition $existingDefinition */
            $existingDefinition = $this->getEntityRepository()->find($workflowDefinition->getName());
            if ($existingDefinition) {
                $workflowDefinition = $existingDefinition->import($workflowDefinition);
            }
        }
        $this->workflowAssembler->assemble($workflowDefinition);

        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $em->persist($workflowDefinition);
            $em->flush($workflowDefinition);
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
        }
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

        $em = $this->getEntityManager();
        $em->remove($workflowDefinition);
        $em->flush();

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
