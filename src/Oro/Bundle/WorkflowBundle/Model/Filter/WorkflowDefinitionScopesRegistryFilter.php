<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;

/**
 * Filters workflow definitions by scopes.
 */
class WorkflowDefinitionScopesRegistryFilter implements WorkflowDefinitionFilterInterface
{
    public function __construct(
        private ScopeManager $scopeManager,
        private ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function filter(Collection $workflowDefinitions): Collection
    {
        $scopeAwareDefinitions = $workflowDefinitions->filter(
            function (WorkflowDefinition $workflowDefinition) {
                return $workflowDefinition->hasScopesConfig();
            }
        );

        if ($scopeAwareDefinitions->isEmpty()) {
            return $workflowDefinitions;
        }

        $scopeMatches = $this->getScopeMatches($scopeAwareDefinitions->getValues());

        foreach ($workflowDefinitions as $key => $workflowDefinition) {
            $name = $workflowDefinition->getName();
            if ($scopeAwareDefinitions->contains($workflowDefinition) && !\in_array($name, $scopeMatches, true)) {
                $workflowDefinitions->remove($key);
            }
        }

        return $workflowDefinitions;
    }

    private function getScopeMatches(array $workflowDefinitions): array
    {
        $scopeDefinitions = $this->getWorkflowDefinitionRepository()->getScopedByNames(
            $this->getNames($workflowDefinitions),
            $this->scopeManager->getCriteria(WorkflowScopeManager::SCOPE_TYPE)
        );

        return $this->getNames($scopeDefinitions);
    }

    private function getNames(array $workflowDefinitions): array
    {
        return array_map(
            function (WorkflowDefinition $workflowDefinition) {
                return $workflowDefinition->getName();
            },
            $workflowDefinitions
        );
    }

    private function getWorkflowDefinitionRepository(): WorkflowDefinitionRepository
    {
        return $this->doctrine->getRepository(WorkflowDefinition::class);
    }
}
