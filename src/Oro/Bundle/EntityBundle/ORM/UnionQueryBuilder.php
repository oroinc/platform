<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder as BaseUnionQueryBuilder;

/**
 * @deprecated since 1.9. Use {@see Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder} instead.
 */
class UnionQueryBuilder extends BaseUnionQueryBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function createQueryBuilder(EntityManager $em, ResultSetMapping $rsm)
    {
        return new SqlQueryBuilder($em, $rsm);
    }
}
