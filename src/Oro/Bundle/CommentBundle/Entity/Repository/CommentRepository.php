<?php

namespace Oro\Bundle\CommentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Repository for managing {@see Comment} entities.
 *
 * Provides specialized query building methods for retrieving comments associated with
 * specific entities. This repository handles the construction of query builders for
 * filtering comments by entity relationships and counting comments for multiple entities.
 */
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
