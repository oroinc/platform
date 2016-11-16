<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionScopesRegistryFilter implements WorkflowDefinitionFilterInterface
{
    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var WorkflowDefinitionRepository
     */
    private $definitionRepository;

    /**
     * @param ScopeManager $scopeManager
     * @param WorkflowDefinitionRepository $definitionRepository
     */
    public function __construct(ScopeManager $scopeManager, WorkflowDefinitionRepository $definitionRepository)
    {
        $this->scopeManager = $scopeManager;
        $this->definitionRepository = $definitionRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(ArrayCollection $workflowDefinitions)
    {
        /**
         * @var ArrayCollection|WorkflowDefinition[] $scopeAwareDefinitions
         * @var ArrayCollection|WorkflowDefinition[] $otherDefinitions
         */
        list($scopeAwareDefinitions, $otherDefinitions) = $workflowDefinitions->partition(
            function (WorkflowDefinition $workflowDefinition) {
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
        $qb = $this->definitionRepository->getByNamesQueryBuilder($this->getNames($workflowDefinitions));
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
}
