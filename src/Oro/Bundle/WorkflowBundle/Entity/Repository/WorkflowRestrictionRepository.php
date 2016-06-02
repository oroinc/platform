<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;

class WorkflowRestrictionRepository extends EntityRepository
{
    /**
     * @param $entityClass
     *
     * @return WorkflowRestriction[]
     */
    public function getClassRestrictions($entityClass)
    {
        return $this->createQueryBuilder('r')
            ->where('r.entityClass = :entityClass')
            ->setParameter('entityClass', $entityClass)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $entityClass
     * @param array  $entityIds
     *
     * @return array
     */
    public function getEntitiesRestrictionsData($entityClass, array $entityIds)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        return $queryBuilder
            ->leftJoin('r.restrictionIdentities', 'ri')
            ->select(
                'r.field',
                'r.mode',
                'r.values',
                ('GROUP_CONCAT(ri.entityId) AS ids')
            )
            ->groupBy('r.id')
            ->where($queryBuilder->expr()->in('ri.entityId', $entityIds))
            ->andWhere('r.entityClass = :entityClass')
            ->setParameter('entityClass', $entityClass)
            ->getQuery()->getArrayResult();
    }
}
