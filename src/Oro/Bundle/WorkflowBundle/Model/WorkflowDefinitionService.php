<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionHandleBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionService
{
    /** @var WorkflowDefinitionHandleBuilder */
    protected $definitionBuilder;

    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var EntityRepository */
    protected $entityRepository;

    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param WorkflowDefinitionHandleBuilder $definitionBuilder
     * @param WorkflowAssembler $workflowAssembler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        WorkflowDefinitionHandleBuilder $definitionBuilder,
        WorkflowAssembler $workflowAssembler,
        DoctrineHelper $doctrineHelper
    ) {
        $this->definitionBuilder = $definitionBuilder;
        $this->workflowAssembler = $workflowAssembler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $configuration
     * @return WorkflowDefinition
     */
    public function createWorkflowDefinitionObject(array $configuration)
    {
        $workflowDefinition = new WorkflowDefinition();
        if (!empty($configuration['name'])) {
            $workflowDefinition->setName($configuration['name']);
        }

        return $workflowDefinition;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param WorkflowDefinition|null $newDefinition
     */
    public function saveWorkflowDefinition(
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
        try {
            $this->getEntityManager()->beginTransaction();
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
     * @param array $configuration
     * @return WorkflowDefinition
     */
    public function buildFromRawConfiguration(array $configuration)
    {
        return $this->definitionBuilder->buildFromRawConfiguration($configuration);
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        if (null === $this->entityManager) {
            $this->entityManager = $this->doctrineHelper
                ->getEntityManagerForClass('OroWorkflowBundle:WorkflowDefinition');
        }

        return $this->entityManager;
    }

    /**
     * @return EntityRepository
     */
    private function getEntityRepository()
    {
        if (null === $this->entityRepository) {
            $this->entityRepository = $this->doctrineHelper
                ->getEntityRepositoryForClass('OroWorkflowBundle:WorkflowDefinition');
        }

        return $this->entityRepository;
    }
}
