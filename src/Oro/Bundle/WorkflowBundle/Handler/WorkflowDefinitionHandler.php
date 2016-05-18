<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionHandleBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;

class WorkflowDefinitionHandler
{
    /** @var WorkflowDefinitionHandleBuilder */
    protected $definitionBuilder;

    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var ManagerRegistry  */
    protected $managerRegistry;

    /** @var string */
    protected $entityClass;

    /** @var EntityRepository */
    protected $entityRepository;

    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param WorkflowDefinitionHandleBuilder $definitionBuilder
     * @param WorkflowAssembler $workflowAssembler
     * @param ManagerRegistry $managerRegistry
     * @param string $entityClass
     */
    public function __construct(
        WorkflowDefinitionHandleBuilder $definitionBuilder,
        WorkflowAssembler $workflowAssembler,
        ManagerRegistry $managerRegistry,
        $entityClass
    ) {
        $this->definitionBuilder = $definitionBuilder;
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

        $this->getEntityManager()->beginTransaction();
        try {
            $this->getEntityManager()->persist($workflowDefinition);
            $this->getEntityManager()->flush($workflowDefinition);
            $this->getEntityManager()->commit();
        } catch (\Exception $exception) {
            $this->getEntityManager()->rollback();
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
        $this->getEntityManager()->remove($workflowDefinition);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        if (null === $this->entityManager) {
            $this->entityManager = $this->managerRegistry->getManagerForClass($this->entityClass);
        }

        return $this->entityManager;
    }

    /**
     * @return EntityRepository
     */
    private function getEntityRepository()
    {
        if (null === $this->entityRepository) {
            $this->entityRepository = $this->getEntityManager()->getRepository($this->entityClass);
        }

        return $this->entityRepository;
    }
}
