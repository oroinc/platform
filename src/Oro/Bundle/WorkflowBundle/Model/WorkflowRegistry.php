<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;

class WorkflowRegistry
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var WorkflowAssembler
     */
    protected $workflowAssembler;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var Workflow[]
     */
    protected $workflowByName = array();

    /**
     * @param ManagerRegistry   $managerRegistry
     * @param WorkflowAssembler $workflowAssembler
     * @param ConfigProvider    $configProvider
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        WorkflowAssembler $workflowAssembler,
        ConfigProvider $configProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->workflowAssembler = $workflowAssembler;
        $this->configProvider = $configProvider;
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
            $definition = $this->getWorkflowDefinitionRepository()->find($name);
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
     * Get Active Workflow that is applicable to entity class
     *
     * @param string $entityClass
     * @return Workflow|null
     */
    public function getActiveWorkflowByEntityClass($entityClass)
    {
        if ($this->configProvider->hasConfig($entityClass)) {
            $entityConfig = $this->configProvider->getConfig($entityClass);
            $activeWorkflowName = $entityConfig->get('active_workflows');

            if ($activeWorkflowName) {
                return $this->getWorkflow($activeWorkflowName, false);
            }
        }

        return null;
    }

    /**
     * Get Active Workflow that is applicable to entity class
     *
     * @param string $entityClass
     * @return Workflow|null
     */
    public function getActiveWorkflowsByEntityClass($entityClass)
    {
        $data = [];

        if ($this->configProvider->hasConfig($entityClass)) {
            $entityConfig = $this->configProvider->getConfig($entityClass);
            $activeWorkflowNames = $entityConfig->get('active_workflows', false, []);

            foreach ($activeWorkflowNames as $name) {
                $data[] = $this->getWorkflow($name, false);
            }
        }

        return $data;
    }

    /**
     * Check is there an active workflow for entity class
     *
     * @param string $entityClass
     * @return bool
     */
    public function hasActiveWorkflowByEntityClass($entityClass)
    {
        return $this->getActiveWorkflowByEntityClass($entityClass) !== null;
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
     * @return EntityRepository
     */
    protected function getWorkflowDefinitionRepository()
    {
        return $this->managerRegistry->getRepository('OroWorkflowBundle:WorkflowDefinition');
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
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass('OroWorkflowBundle:WorkflowDefinition');

        if (!$entityManager->getUnitOfWork()->isInIdentityMap($definition)) {
            $definitionName = $definition->getName();

            $definition = $this->getWorkflowDefinitionRepository()->find($definitionName);
            if (!$definition) {
                throw new WorkflowNotFoundException($definitionName);
            }
        }

        return $definition;
    }
}
