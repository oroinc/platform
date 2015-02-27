<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;

/**
 * fix of doctrine error with Same Field, Multiple Values, Criteria and QueryBuilder
 * http://www.doctrine-project.org/jira/browse/DDC-2798
 * TODO remove this file when doctrine version >= 2.5 in scope of BAP-5577
 */
class QueryBuilderHelper
{
    /**
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     */
    public static function addCriteria(QueryBuilder $qb, Criteria $criteria)
    {
        $rootAlias = $qb->getRootAlias();
        $visitor = new QueryExpressionVisitor($rootAlias);

        if ($whereExpression = $criteria->getWhereExpression()) {
            $qb->andWhere($visitor->dispatch($whereExpression));
            foreach ($visitor->getParameters() as $parameter) {
                $qb->getParameters()->add($parameter);
            }
        }

        if ($criteria->getOrderings()) {
            foreach ($criteria->getOrderings() as $sort => $order) {
                $qb->addOrderBy($rootAlias . '.' . $sort, $order);
            }
        }

        // Overwrite limits only if they was set in criteria
        if (($firstResult = $criteria->getFirstResult()) !== null) {
            $qb->setFirstResult($firstResult);
        }
        if (($maxResults = $criteria->getMaxResults()) !== null) {
            $qb->setMaxResults($maxResults);
        }
    }
}
