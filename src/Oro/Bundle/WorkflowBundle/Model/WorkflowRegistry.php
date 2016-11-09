<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;

class WorkflowRegistry
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var Workflow[] */
    protected $workflowByName = [];

    /** @var array */
    protected $workflowByEntityClass = [];

    /** @var FeatureChecker  */
    protected $featureChecker;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param WorkflowAssembler $workflowAssembler
     * @param FeatureChecker $featureChecker
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        WorkflowAssembler $workflowAssembler,
        FeatureChecker $featureChecker
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->workflowAssembler = $workflowAssembler;
        $this->featureChecker = $featureChecker;
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
        if (!is_string($name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected value is workflow name string. But got %s',
                    is_object($name) ? get_class($name) : gettype($name)
                )
            );
        }

        if (!array_key_exists($name, $this->workflowByName)) {
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
        if (!array_key_exists($workflowName, $this->workflowByName)) {
            $workflow = $this->workflowAssembler->assemble($definition);
            $this->workflowByName[$workflowName] = $workflow;
        }

        return $this->refreshWorkflow($this->workflowByName[$workflowName]);
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function hasActiveWorkflowsByEntityClass($entityClass)
    {
        $class = ClassUtils::getRealClass($entityClass);

        if (array_key_exists($class, $this->workflowByEntityClass)) {
            return true;
        }

        $activeWorkflowDefinitions = $this->getEntityRepository()->findActiveForRelatedEntity($class);

        $items = $this->filterWorkflowDefinitionsByFeatures($activeWorkflowDefinitions);

        return count($items) > 0;
    }

    /**
     * Get Active Workflows that applicable to entity class
     *
     * @param string $entityClass
     * @return Workflow[]|ArrayCollection Named collection of active Workflow instances
     *                                    with structure: ['workflowName' => Workflow $workflowInstance]
     */
    public function getActiveWorkflowsByEntityClass($entityClass)
    {
        $class = ClassUtils::getRealClass($entityClass);

        if (!array_key_exists($class, $this->workflowByEntityClass)) {
            $this->workflowByEntityClass[$class] = $this->getAssembledWorkflows(
                $this->getEntityRepository()->findActiveForRelatedEntity($class)
            );
        }

        return $this->workflowByEntityClass[$class];
    }

    /**
     * Get Active Workflows by active groups
     *
     * @param array $groupNames
     * @return Workflow[]|array
     */
    public function getActiveWorkflowsByActiveGroups(array $groupNames)
    {
        $groupNames = array_map('strtolower', $groupNames);
        $definitions = array_filter(
            $this->getEntityRepository()->findActive(),
            function (WorkflowDefinition $definition) use ($groupNames) {
                $exclusiveActiveGroups = $definition->getExclusiveActiveGroups();

                return (bool)array_intersect($groupNames, $exclusiveActiveGroups);
            }
        );

        return $this->getAssembledWorkflows($definitions)->getValues();
    }

    /**
     * Returns named collection of active Workflow instances with structure:
     *      ['workflowName' => Workflow $workflowInstance]
     *
     * @return Workflow[]|ArrayCollection
     */
    public function getActiveWorkflows()
    {
        return $this->getAssembledWorkflows($this->getEntityRepository()->findActive());
    }

    /**
     * @param WorkflowDefinition[] $definitions
     *
     * @return ArrayCollection
     */
    private function getAssembledWorkflows(array $definitions)
    {
        $workflows = new ArrayCollection();
        foreach ($this->filterWorkflowDefinitionsByFeatures($definitions) as $definition) {
            $workflows->set($definition->getName(), $this->getAssembledWorkflow($definition));
        }

        return $workflows;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(WorkflowDefinition::class);
    }

    /**
     * @return WorkflowDefinitionRepository
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
    
    /**
     * @param WorkflowDefinition[] $workflowDefinitions
     * @return WorkflowDefinition[]|array
     */
    protected function filterWorkflowDefinitionsByFeatures(array $workflowDefinitions)
    {
        return array_filter(
            $workflowDefinitions,
            function (WorkflowDefinition $definition) {
                return $this->featureChecker->isResourceEnabled(
                    $definition->getName(),
                    FeatureConfigurationExtension::WORKFLOWS_NODE_NAME
                );
            }
        );
    }
}
