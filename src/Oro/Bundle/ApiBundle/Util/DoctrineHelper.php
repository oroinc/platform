<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper as BaseHelper;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;

class DoctrineHelper extends BaseHelper
{
    /**
     * Applies the given joins for the query builder
     *
     * @param QueryBuilder $qb
     * @param array|null   $joins
     */
    public function applyJoins(QueryBuilder $qb, $joins)
    {
        QueryUtils::applyJoins($qb, $joins);
    }

    /**
     * Gets ORDER BY expression that can be used to sort a collection by entity identifier
     *
     * @param string $entityClass
     *
     * @return array|null
     */
    public function getOrderByIdentifier($entityClass)
    {
        $ids = $this->getEntityMetadata($entityClass)->getIdentifierFieldNames();
        if (empty($ids)) {
            return null;
        }

        $orderBy = [];
        foreach ($ids as $pk) {
            $orderBy[$pk] = Criteria::ASC;
        }

        return $orderBy;
    }
}
