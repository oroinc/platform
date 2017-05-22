<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilters;

class WorkflowRegistry
{
    const CACHE_TTL = 0;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var WorkflowAssembler */
    protected $workflowAssembler;

    /** @var Workflow[] */
    protected $workflowByName = [];

    /** @var WorkflowDefinitionFilters */
    protected $definitionFilters;

    /** @var CacheProvider */
    protected $cacheProvider;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param WorkflowAssembler $workflowAssembler
     * @param WorkflowDefinitionFilters $definitionFilters
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        WorkflowAssembler $workflowAssembler,
        WorkflowDefinitionFilters $definitionFilters
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->workflowAssembler = $workflowAssembler;
        $this->definitionFilters = $definitionFilters;
    }

    /**
     * @param CacheProvider $cacheProvider
     */
    public function setCacheProvider(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
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
            $definition = $this->getEntityRepository()->find($name);
        } else {
            $definition = $this->workflowByName[$name]->getDefinition();
        }

        if ($definition) {
            $definition = $this->processDefinitionFilters(new ArrayCollection([$definition]))->first();
        }

        if (!$definition) {
            if ($exceptionOnNotFound) {
                throw new WorkflowNotFoundException($name);
            }

            return null;
        }

        return $this->getAssembledWorkflow($definition);
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
        return $this->isWorkflowsArrayEmpty($this->getActiveWorkflowDefinitionsByRelatedEntityClass($entityClass));
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function hasWorkflowsByEntityClass($entityClass)
    {
        return $this->isWorkflowsArrayEmpty($this->getWorkflowDefinitionsByRelatedEntityClass($entityClass));
    }

    /**
     * @param array|WorkflowDefinition[] $workflowDefinitions
     * @return bool
     */
    private function isWorkflowsArrayEmpty(array $workflowDefinitions)
    {
        $items = $this->processDefinitionFilters(
            $this->getNamedDefinitionsCollection($workflowDefinitions)
        );

        return !$items->isEmpty();
    }

    /**
     * Get Active Workflows that applicable to entity class
     *
     * @param string $entityClass
     * @return Workflow[]|Collection Named collection of active Workflow instances
     *                               with structure: ['workflowName' => Workflow $workflowInstance]
     */
    public function getActiveWorkflowsByEntityClass($entityClass)
    {
        return $this->getAssembledWorkflows($this->getActiveWorkflowDefinitionsByRelatedEntityClass($entityClass));
    }

    /**
     * Get Workflows that applicable to entity class
     *
     * @param string $entityClass
     * @return Workflow[]|Collection Named collection of Workflow instances
     *                               with structure: ['workflowName' => Workflow $workflowInstance]
     */
    public function getWorkflowsByEntityClass($entityClass)
    {
        return $this->getAssembledWorkflows($this->getWorkflowDefinitionsByRelatedEntityClass($entityClass));
    }

    /**
     * Get Active Workflows by active groups
     *
     * @param array $groupNames
     * @return Workflow[]|Collection Named collection of active Workflow instances
     *                               with structure: ['workflowName' => Workflow $workflowInstance]
     */
    public function getActiveWorkflowsByActiveGroups(array $groupNames)
    {
        $groupNames = array_map('strtolower', $groupNames);

        $definitions = array_filter(
            $this->getActiveWorkflowDefinitions(),
            function (WorkflowDefinition $definition) use ($groupNames) {
                $exclusiveActiveGroups = $definition->getExclusiveActiveGroups();

                return (bool)array_intersect($groupNames, $exclusiveActiveGroups);
            }
        );

        return $this->getAssembledWorkflows($definitions);
    }

    /**
     * Returns named collection of active Workflow instances with structure:
     *      ['workflowName' => Workflow $workflowInstance]
     *
     * @return Workflow[]|Collection
     */
    public function getActiveWorkflows()
    {
        return $this->getAssembledWorkflows($this->getActiveWorkflowDefinitions());
    }

    /**
     * @param WorkflowDefinition[] $definitions
     *
     * @return Collection
     */
    private function getAssembledWorkflows(array $definitions)
    {
        $definitions = $this->getNamedDefinitionsCollection($definitions);

        return $this->processDefinitionFilters($definitions)
            ->map(
                function (WorkflowDefinition $workflowDefinition) {
                    return $this->getAssembledWorkflow($workflowDefinition);
                }
            );
    }

    /**
     * @param Collection|WorkflowDefinition[] $workflowDefinitions
     * @return Collection|WorkflowDefinition[]
     */
    private function processDefinitionFilters(Collection $workflowDefinitions)
    {
        if ($workflowDefinitions->isEmpty()) {
            return $workflowDefinitions;
        }

        foreach ($this->definitionFilters->getFilters() as $definitionFilter) {
            $workflowDefinitions = $definitionFilter->filter($workflowDefinitions);
        }

        return $workflowDefinitions;
    }

    /**
     * @param WorkflowDefinition[] $workflowDefinitions
     * @return Collection|Workflow[]
     */
    private function getNamedDefinitionsCollection(array $workflowDefinitions)
    {
        $workflows = new ArrayCollection();
        foreach ($workflowDefinitions as $definition) {
            $workflowName = $definition->getName();
            /** @var WorkflowDefinition $definition */
            $workflows->set($workflowName, $definition);
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
     * @return WorkflowDefinition[]
     */
    protected function getActiveWorkflowDefinitions()
    {
        $cacheId = 'active_workflow_definitions';
        $definitions = $this->fetchCache($cacheId);

        if (false === $definitions) {
            $definitions = $this->getEntityRepository()->findActive();
            $this->saveCache($cacheId, $definitions);
        }

        return $definitions;
    }

    /**
     * @param string $entityClass
     *
     * @return WorkflowDefinition[]
     */
    protected function getWorkflowDefinitionsByRelatedEntityClass($entityClass)
    {
        $cacheId = 'workflow_definitions_for_' . $entityClass;

        $definitions = $this->fetchCache($cacheId);

        if (false === $definitions) {
            $definitions = $this->getEntityRepository()->findForRelatedEntity($entityClass);
            $this->saveCache($cacheId, $definitions);
        }

        return $definitions;
    }

    /**
     * @param string $entityClass
     *
     * @return WorkflowDefinition[]
     */
    protected function getActiveWorkflowDefinitionsByRelatedEntityClass($entityClass)
    {
        $cacheId = 'active_workflow_definitions_for_' . $entityClass;

        $definitions = $this->fetchCache($cacheId);

        if (false === $definitions) {
            $definitions = $this->getEntityRepository()->findActiveForRelatedEntity($entityClass);
            $this->saveCache($cacheId, $definitions);
        }

        return $definitions;
    }

    /**
     * @param string $id
     *
     * @return false|mixed
     */
    private function fetchCache($id)
    {
        if ($this->cacheProvider) {
            return $this->cacheProvider->fetch($id);
        }

        return false;
    }

    /**
     * @param string $id
     * @param mixed $data
     *
     * @return false|mixed
     */
    private function saveCache($id, $data)
    {
        if ($this->cacheProvider) {
            return $this->cacheProvider->save($id, $data, self::CACHE_TTL);
        }

        return false;
    }
}
