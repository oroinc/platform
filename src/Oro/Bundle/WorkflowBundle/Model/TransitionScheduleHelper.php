<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

class TransitionScheduleHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @var WorkflowManager
     */
    private $workflowManager;

    /**
     * @param ManagerRegistry $registry
     * @param WorkflowManager $workflowManager
     */
    public function __construct(ManagerRegistry $registry, WorkflowManager $workflowManager)
    {
        $this->registry = $registry;
        $this->workflowManager = $workflowManager;
    }

    public function getWorkflowItemIds($workflowName, $transitionName)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowName);

        $query = $this->createQuery(
            $this->getRelatedSteps($workflow->getStepManager(), $transitionName),
            $workflow->getDefinition()->getRelatedEntity(),
            $workflow->getTransitionManager()->getTransition($transitionName)->getScheduleFilter()
        );

        $result = $query->getArrayResult();

        $ids = [];
        foreach ($result as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    /**
     * @param StepManager $stepManager
     * @param $transitionName
     * @return array
     */
    private function getRelatedSteps(StepManager $stepManager, $transitionName)
    {
        $relatedSteps = [];
        foreach ($stepManager->getRelatedTransitionSteps($transitionName) as $step) {
            $relatedSteps[] = $step->getName();
        }

        return $relatedSteps;
    }

    /**
     * @param array $workflowSteps
     * @param string $entityClass
     * @param string $dqlFilter optional dql WHERE clause
     * @return array|\Doctrine\ORM\Query
     */
    public function createQuery(array $workflowSteps, $entityClass, $dqlFilter)
    {
        if (!$workflowSteps) {
            return [];
        }

        $queryBuilder = $this
            ->getEntityRepositoryForClass($entityClass)
            ->createQueryBuilder('e')
            ->select('wi.id')
            ->innerJoin('e.workflowItem', 'wi')
            ->innerJoin('e.workflowStep', 'ws')
            ->innerJoin('wi.definition', 'wd');

        $queryBuilder
            ->where($queryBuilder->expr()->in('ws.name', ':workflowSteps'))
            ->setParameter('workflowSteps', $workflowSteps);

        if ($dqlFilter) {
            $queryBuilder->andWhere($dqlFilter);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param string $entityClass
     * @return EntityRepository
     */
    private function getEntityRepositoryForClass($entityClass)
    {
        return $this->registry
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }
}
