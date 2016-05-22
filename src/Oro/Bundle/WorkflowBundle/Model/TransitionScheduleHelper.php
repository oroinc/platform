<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class TransitionScheduleHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param WorkflowStep[] $workflowSteps
     * @param string $entityClass
     * @param string $dqlFilter
     * @return array
     */
    public function getWorkflowItemIds(array $workflowSteps, $entityClass, $dqlFilter)
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
            ->where($queryBuilder->expr()->in('ws.id', ':workflowSteps'))
            ->setParameter('workflowSteps', $workflowSteps);

        if ($dqlFilter) {
            $queryBuilder->andWhere($dqlFilter);
        }

        return $queryBuilder->getQuery()->getArrayResult();
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
