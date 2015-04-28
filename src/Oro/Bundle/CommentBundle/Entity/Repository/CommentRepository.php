<?php

namespace Oro\Bundle\CommentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CommentRepository extends EntityRepository
{
    /**
     * @param string $fieldName
     * @param string $entityId
     *
     * @return QueryBuilder
     */
    public function getBaseQueryBuilder($fieldName, $entityId)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere('c.' . $fieldName . ' in (:param1)');
        $qb->setParameter('param1', $entityId);

        return $qb;
    }

    /**
     * @param string $fieldName
     * @param string $entityId
     *
     * @return QueryBuilder
     */
    public function getNumberOfComment($fieldName, $entityId)
    {
        $qb = $this->getBaseQueryBuilder($fieldName, $entityId);
        $qb->select($qb->expr()->count('c.id'));

        return $qb;
    }
}
