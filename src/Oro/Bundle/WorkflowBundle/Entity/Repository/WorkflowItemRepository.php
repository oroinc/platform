<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class WorkflowItemRepository extends EntityRepository
{
    /**
     * Get workflow item associated with entity.
     *
     * @param string $entityClass
     * @param int $entityIdentifier
     * @return WorkflowItem|null
     */
    public function findByEntityMetadata($entityClass, $entityIdentifier)
    {
        $qb = $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $entityClass
     * @param int $entityIdentifier
     * @return QueryBuilder
     */
    protected function getWorkflowQueryBuilder($entityClass, $entityIdentifier)
    {
        $qb = $this->createQueryBuilder('wi')
            ->innerJoin('wi.definition', 'wd')
            ->where('wd.relatedEntity = :entityClass')
            ->andWhere('wi.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityIdentifier);

        return $qb;
    }
}
