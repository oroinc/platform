<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;

class WorkflowRegistry
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var WorkflowSystemConfigManager */
    protected $configManager;

    /** @var Workflow[] */
    protected $workflowByName = [];

    /**
     * @param ManagerRegistry $managerRegistry
     * @param WorkflowAssembler $workflowAssembler
     * @param WorkflowSystemConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        WorkflowAssembler $workflowAssembler,
        WorkflowSystemConfigManager $configManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->workflowAssembler = $workflowAssembler;
        $this->configManager = $configManager;
    }

    /**
     * Get Workflow by name
     *
     * @param string $name
     * @param bool $exceptionOnNotFound
     * @return Workflow|null
     * @throws WorkflowNotFoundException
     */
    public function getWorkflow($name, $exceptionOnNotFound = true)
    {
        if (!isset($this->workflowByName[$name])) {
            /** @var WorkflowDefinition $definition */
            $definition = $this->getEntityRepository()->find($name);
            if (!$definition) {
                if ($exceptionOnNotFound) {
                    throw new WorkflowNotFoundException($name);
                } else {
                    return null;
                }
            }

            return $this->getAssembledWorkflow($definition);
        }

        return $this->refreshWorkflow($this->workflowByName[$name]);
    }

    /**
     * Get Workflow by WorkflowDefinition
     *
     * @param WorkflowDefinition $definition
     * @return Workflow
     */
    protected function getAssembledWorkflow(WorkflowDefinition $definition)
    {
        $workflowName = $definition->getName();
        if (!isset($this->workflowByName[$workflowName])) {
            $workflow = $this->workflowAssembler->assemble($definition);
            $this->workflowByName[$workflowName] = $workflow;
        }

        return $this->refreshWorkflow($this->workflowByName[$workflowName]);
    }

    /**
     * Get Active Workflows that applicable to entity class
     *
     * @param string $entityClass
     * @return Workflow[]
     */
    public function getActiveWorkflowsByEntityClass($entityClass)
    {
        $class = ClassUtils::getRealClass($entityClass);

        return array_filter(
            array_map(
                function ($activeWorkflowName) {
                    return $this->getWorkflow($activeWorkflowName, false);
                },
                $this->configManager->getActiveWorkflowNamesByEntity($entityClass)
            ),
            function (Workflow $workflow) use ($class) {
                return $workflow->getDefinition()->getRelatedEntity() === $class;
            }
        );
    }

    /**
     * Check is there an active workflow for entity class
     *
     * @param string $entityClass
     * @return bool
     */
    public function hasActiveWorkflowsByEntityClass($entityClass)
    {
        return count($this->getActiveWorkflowsByEntityClass($entityClass)) > 0;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(WorkflowDefinition::class);
    }

    /**
     * @return EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository(WorkflowDefinition::class);
    }

    /**
     * Ensure that all database entities in workflow are still in Doctrine Unit of Work
     *
     * @param Workflow $workflow
     * @return Workflow
     * @throws WorkflowNotFoundException
     */
    protected function refreshWorkflow(Workflow $workflow)
    {
        $refreshedDefinition = $this->refreshWorkflowDefinition($workflow->getDefinition());
        $workflow->setDefinition($refreshedDefinition);

        return $workflow;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowDefinition
     * @throws WorkflowNotFoundException
     */
    protected function refreshWorkflowDefinition(WorkflowDefinition $definition)
    {
        if (!$this->getEntityManager()->getUnitOfWork()->isInIdentityMap($definition)) {
            $definitionName = $definition->getName();

            $definition = $this->getEntityRepository()->find($definitionName);
            if (!$definition) {
                throw new WorkflowNotFoundException($definitionName);
            }
        }

        return $definition;
    }
}
