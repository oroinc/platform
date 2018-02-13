<?php

namespace Oro\Bundle\CommentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class CommentRepository extends EntityRepository
{
    /**
     * @param string $fieldName
     * @param int[] $entityIds
     *
     * @return QueryBuilder
     */
    public function getBaseQueryBuilder($fieldName, $entityIds)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere($qb->expr()->in(QueryBuilderUtil::getField('c', $fieldName), ':param1'));
        $qb->setParameter('param1', $entityIds);

        return $qb;
    }

    /**
     * @param string $fieldName
     * @param int[] $entityIds
     *
     * @return QueryBuilder
     */
    public function getNumberOfComment($fieldName, $entityIds)
    {
        $qb = $this->getBaseQueryBuilder($fieldName, $entityIds);
        $qb->select($qb->expr()->count('c.id'));

        return $qb;
    }
}
