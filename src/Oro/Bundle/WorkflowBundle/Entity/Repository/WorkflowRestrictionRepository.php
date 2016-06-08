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
            ->select(
                'r.id',
                'r.entityClass',
                'IDENTITY(r.step) AS step',
                'r.mode',
                'r.field',
                'r.values',
                'd.relatedEntity',
                'd.name AS workflowName'
            )
            ->join('r.definition', 'd')
            ->where('r.entityClass = :entityClass')
            ->setParameter('entityClass', $entityClass)
            ->getQuery()
            ->getArrayResult();
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
                'r.id',
                'r.field',
                'r.mode',
                'r.values',
                'GROUP_CONCAT(ri.entityId) AS ids'
            )
            ->groupBy('r.id')
            ->where($queryBuilder->expr()->in('ri.entityId', $entityIds))
            ->andWhere('r.entityClass = :entityClass')
            ->setParameter('entityClass', $entityClass)
            ->getQuery()->getArrayResult();
    }
}
