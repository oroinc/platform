<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class WorkflowStepRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return WorkflowStep[]
     */
    public function findByIds(array $ids)
    {
        $queryBuilder = $this->createQueryBuilder('ws');

        return $queryBuilder
            ->indexBy('ws', 'ws.id')
            ->where($queryBuilder->expr()->in('ws.id', ':ids'))
            ->setParameter('ids', $ids)
            ->orderBy('ws.id')
            ->getQuery()
            ->getResult();
    }
}
