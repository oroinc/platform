<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionScopesRegistryFilter implements WorkflowDefinitionFilterInterface
{
    /** @var ScopeManager */
    private $scopeManager;

    /** @var ManagerRegistry */
    private $managerRegistry;

    /**
     * @param ScopeManager $scopeManager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ScopeManager $scopeManager, ManagerRegistry $managerRegistry)
    {
        $this->scopeManager = $scopeManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Collection $workflowDefinitions)
    {
        /**
         * @var Collection|WorkflowDefinition[] $scopeAwareDefinitions
         * @var Collection|WorkflowDefinition[] $otherDefinitions
         */
        list($scopeAwareDefinitions, $otherDefinitions) = $workflowDefinitions->partition(
            function ($name, WorkflowDefinition $workflowDefinition) {
                return count($workflowDefinition->getScopesConfig()) !== 0;
            }
        );

        $scopeMatches = $this->getScopeMatched($scopeAwareDefinitions->getValues());

        foreach ($scopeAwareDefinitions as $name => $workflowDefinition) {
            if (in_array($workflowDefinition->getName(), $scopeMatches, true)) {
                $otherDefinitions->set($name, $workflowDefinition);
            }
        }

        return $otherDefinitions;
    }

    /**
     * @param array $workflowDefinitions
     * @return array
     */
    private function getScopeMatched(array $workflowDefinitions)
    {
        $qb = $this->getWorkflowDefinitionRepository()->getByNamesQueryBuilder($this->getNames($workflowDefinitions));
        $qb->join('wd.scopes', 'scopes', Join::WITH);

        $criteria = $this->scopeManager->getCriteria('workflow_definition');
        $criteria->applyToJoinWithPriority($qb, 'scopes');

        return $this->getNames($qb->getQuery()->getResult());
    }

    /**
     * @param array $workflowDefinitions
     * @return array|string[]
     */
    private function getNames(array $workflowDefinitions)
    {
        return array_map(
            function (WorkflowDefinition $workflowDefinition) {
                return $workflowDefinition->getName();
            },
            $workflowDefinitions
        );
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    private function getWorkflowDefinitionRepository()
    {
        return $this->managerRegistry
            ->getManagerForClass(WorkflowDefinition::class)
            ->getRepository(WorkflowDefinition::class);
    }
}
