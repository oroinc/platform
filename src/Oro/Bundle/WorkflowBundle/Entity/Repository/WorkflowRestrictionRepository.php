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
    public function getRestrictions($entityClass)
    {
        return $this->createQueryBuilder('r')
            ->where('r.entityClass = :entityClass')
            ->setParameter('entityClass', $entityClass)
            ->getQuery()
            ->getResult();
    }
}
