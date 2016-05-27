<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class TransitionQueryFactory
{
    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->registry = $managerRegistry;
    }

    /**
     * @param array $workflowSteps
     * @param string $entityClass
     * @param string $dqlFilter optional dql WHERE clause
     * @return array|Query
     */
    public function create(array $workflowSteps, $entityClass, $dqlFilter = null)
    {
        if (count($workflowSteps) === 0) {
            throw new \InvalidArgumentException(
                'At least one step, in which transition can be performed from, must be provided.'
            );
        }

        $queryBuilder = $this->getEntityRepositoryForClass($entityClass)
            ->createQueryBuilder('e')
            ->select('wi.id')
            ->innerJoin('e.workflowItem', 'wi')
            ->innerJoin('e.workflowStep', 'ws')
            ->innerJoin('wi.definition', 'wd');

        $queryBuilder->where($queryBuilder->expr()->in('ws.name', ':workflowSteps'))
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
        return $this->registry->getManagerForClass($entityClass)->getRepository($entityClass);
    }
}
