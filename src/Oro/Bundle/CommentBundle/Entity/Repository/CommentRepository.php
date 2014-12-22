<?php

namespace Oro\Bundle\CommentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CommentRepository extends EntityRepository
{
    /**
     * @param string $orderField
     * @param string $orderDirection
     *
     * @return QueryBuilder
     */
    public function getBaseQueryBuilder($orderField = 'updatedAt', $orderDirection = 'DESC')
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.' . $orderField, $orderDirection);
    }
}
