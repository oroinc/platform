<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilters;

/**
 * The registry of workflows and workflow definitions.
 */
class WorkflowRegistry
{
    /** @var Workflow[] */
    private array $workflowByName = [];

    public function __construct(
        protected DoctrineHelper $doctrineHelper,
        protected WorkflowAssembler $workflowAssembler,
        protected WorkflowDefinitionFilters $definitionFilters
    ) {
    }

    public function getWorkflow(string $name, bool $exceptionOnNotFound = true): ?Workflow
    {
        if (\array_key_exists($name, $this->workflowByName)) {
            $definition = $this->workflowByName[$name]->getDefinition();
        } else {
            $definition = $this->getEntityRepository()->find($name);
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

    protected function getAssembledWorkflow(WorkflowDefinition $definition): Workflow
    {
        $workflowName = $definition->getName();
        if (!\array_key_exists($workflowName, $this->workflowByName)) {
            $this->workflowByName[$workflowName] = $this->workflowAssembler->assemble($definition);
        }

        return $this->refreshWorkflow($this->workflowByName[$workflowName]);
    }

    public function hasActiveWorkflowsByEntityClass(string $entityClass): bool
    {
        return $this->isWorkflowsArrayEmpty($this->getEntityRepository()->findActiveForRelatedEntity($entityClass));
    }

    public function hasWorkflowsByEntityClass(string $entityClass): bool
    {
        return $this->isWorkflowsArrayEmpty($this->getEntityRepository()->findForRelatedEntity($entityClass));
    }

    /**
     * @param WorkflowDefinition[] $workflowDefinitions
     *
     * @return bool
     */
    private function isWorkflowsArrayEmpty(array $workflowDefinitions): bool
    {
        $items = $this->processDefinitionFilters($this->getNamedDefinitionsCollection($workflowDefinitions));

        return !$items->isEmpty();
    }

    /**
     * @return Collection<string, Workflow> [workflow name => workflow, ...]
     */
    public function getActiveWorkflowsByEntityClass(string $entityClass): Collection
    {
        return $this->getAssembledWorkflows($this->getEntityRepository()->findActiveForRelatedEntity($entityClass));
    }

    /**
     * @return Collection<string, Workflow> [workflow name => workflow, ...]
     */
    public function getWorkflowsByEntityClass(string $entityClass): Collection
    {
        return $this->getAssembledWorkflows($this->getEntityRepository()->findForRelatedEntity($entityClass));
    }

    /**
     * @return Collection<string, Workflow> [workflow name => workflow, ...]
     */
    public function getActiveWorkflowsByActiveGroups(array $groupNames): Collection
    {
        $groupNames = array_map('strtolower', $groupNames);

        $definitions = array_filter(
            $this->getEntityRepository()->findActive(),
            function (WorkflowDefinition $definition) use ($groupNames) {
                $exclusiveActiveGroups = $definition->getExclusiveActiveGroups();

                return (bool)array_intersect($groupNames, $exclusiveActiveGroups);
            }
        );

        return $this->getAssembledWorkflows($definitions);
    }

    /**
     * @return Collection<string, Workflow> [workflow name => workflow, ...]
     */
    public function getActiveWorkflows(): Collection
    {
        return $this->getAssembledWorkflows($this->getEntityRepository()->findActive());
    }

    /**
     * @param WorkflowDefinition[] $definitions
     *
     * @return Collection<string, Workflow> [workflow name => workflow, ...]
     */
    private function getAssembledWorkflows(array $definitions): Collection
    {
        return $this->processDefinitionFilters($this->getNamedDefinitionsCollection($definitions))
            ->map(function (WorkflowDefinition $workflowDefinition) {
                return $this->getAssembledWorkflow($workflowDefinition);
            });
    }

    /**
     * @param Collection<int, WorkflowDefinition> $workflowDefinitions
     *
     * @return Collection<int, WorkflowDefinition>
     */
    private function processDefinitionFilters(Collection $workflowDefinitions): Collection
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
     *
     * @return Collection<int, Workflow>
     */
    private function getNamedDefinitionsCollection(array $workflowDefinitions): Collection
    {
        $workflows = new ArrayCollection();
        foreach ($workflowDefinitions as $definition) {
            $workflows->set($definition->getName(), $definition);
        }

        return $workflows;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrineHelper->getEntityManagerForClass(WorkflowDefinition::class);
    }

    protected function getEntityRepository(): WorkflowDefinitionRepository
    {
        return $this->getEntityManager()->getRepository(WorkflowDefinition::class);
    }

    /**
     * Ensure that all database entities in workflow are still in Doctrine Unit of Work
     *
     * @throws WorkflowNotFoundException
     */
    protected function refreshWorkflow(Workflow $workflow): Workflow
    {
        $refreshedDefinition = $this->refreshWorkflowDefinition($workflow->getDefinition());
        $workflow->setDefinition($refreshedDefinition);

        return $workflow;
    }

    /**
     * @throws WorkflowNotFoundException
     */
    protected function refreshWorkflowDefinition(WorkflowDefinition $definition): WorkflowDefinition
    {
        if ($this->getEntityManager()->getUnitOfWork()->isInIdentityMap($definition)) {
            return $definition;
        }

        $definitionName = $definition->getName();
        $foundDefinition = $this->getEntityRepository()->find($definitionName);
        if (!$foundDefinition) {
            throw new WorkflowNotFoundException($definitionName);
        }

        return $foundDefinition;
    }
}
