<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
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
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var Workflow[]
     */
    protected $workflowByName = array();

    /**
     * @param ManagerRegistry $managerRegistry
     * @param WorkflowAssembler $workflowAssembler
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        WorkflowAssembler $workflowAssembler,
        ConfigProviderInterface $configProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->workflowAssembler = $workflowAssembler;
        $this->configProvider = $configProvider;
    }

    /**
     * Get Workflow by name
     *
     * @param string $workflowName
     * @return Workflow
     * @throws WorkflowNotFoundException
     */
    public function getWorkflow($workflowName)
    {
        if (!isset($this->workflowByName[$workflowName])) {
            $workflowDefinition = $this->findWorkflowDefinition($workflowName);
            if (!$workflowDefinition) {
                throw new WorkflowNotFoundException($workflowName);
            }
            return $this->getAssembledWorkflow($workflowDefinition);
        }

        return $this->workflowByName[$workflowName];
    }

    /**
     * Get Workflow by WorkflowDefinition
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return Workflow
     */
    protected function getAssembledWorkflow(WorkflowDefinition $workflowDefinition)
    {
        $workflowName = $workflowDefinition->getName();
        if (!isset($this->workflowByName[$workflowName])) {
            $workflow = $this->assembleWorkflow($workflowDefinition);
            $this->workflowByName[$workflowName] = $workflow;
        }

        return $this->workflowByName[$workflowName];
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
            $activeWorkflowName = $entityConfig->get('active_workflow');

            if ($activeWorkflowName) {
                $workflows = $this->getWorkflowsByEntityClass($entityClass, $activeWorkflowName);

                if (array_key_exists($activeWorkflowName, $workflows)) {
                    return $workflows[$activeWorkflowName];
                }
            }
        }

        return null;
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
     * Get Workflows that is applicable to entity class
     *
     * @param string $entityClass
     * @param string|null $workflowName
     * @return Workflow[]
     */
    public function getWorkflowsByEntityClass($entityClass, $workflowName = null)
    {
        $result = array();
        $workflowDefinitions = $this->getWorkflowDefinitionRepository()
            ->findByEntityClass($entityClass, $workflowName);

        foreach ($workflowDefinitions as $workflowDefinition) {
            $result[$workflowDefinition->getName()] = $this->getAssembledWorkflow($workflowDefinition);
        }

        return $result;
    }

    /**
     * Find WorkflowDefinition
     *
     * @param string $name
     * @return WorkflowDefinition|null
     */
    protected function findWorkflowDefinition($name)
    {
        return $this->getWorkflowDefinitionRepository()->find($name);
    }

    /**
     * Assembles Workflow by WorkflowDefinition
     *
     * @param WorkflowDefinition $workflowDefinition
     * @return Workflow
     */
    protected function assembleWorkflow(WorkflowDefinition $workflowDefinition)
    {
        return $this->workflowAssembler->assemble($workflowDefinition);
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    protected function getWorkflowDefinitionRepository()
    {
        return $this->managerRegistry->getRepository('OroWorkflowBundle:WorkflowDefinition');
    }
}
