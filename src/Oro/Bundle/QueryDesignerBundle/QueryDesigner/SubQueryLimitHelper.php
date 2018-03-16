<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * This class makes possible to apply subquery with limit to query builder.
 * Because of reason that it is not possible on DQL. It applies "hook" to passed query builder.
 * And this hook will be processed by SqlWalker which actually adds limit to row sql subquery.
 */
class SubQueryLimitHelper
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param int $limit
     * @param string $fieldName Identifier which will be used in Select part
     * @return QueryBuilder
     */
    public function setLimit(QueryBuilder $queryBuilder, $limit, $fieldName)
    {
        $uniqueIdentifier = QueryBuilderUtil::generateParameterName(SqlWalker::WALKER_HOOK_LIMIT_KEY);

        $walkerHook = "'$uniqueIdentifier' = '$uniqueIdentifier'";
        $queryBuilder->andWhere($walkerHook);
        $queryBuilder
            ->getEntityManager()
            ->getConfiguration()
            ->setDefaultQueryHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class);

        $queryBuilder
            ->getEntityManager()
            ->getConfiguration()
            ->setDefaultQueryHint(SqlWalker::WALKER_HOOK_LIMIT_KEY, $walkerHook);

        $queryBuilder
            ->getEntityManager()
            ->getConfiguration()
            ->setDefaultQueryHint(SqlWalker::WALKER_HOOK_LIMIT_VALUE, $limit);

        $queryBuilder
            ->getEntityManager()
            ->getConfiguration()
            ->setDefaultQueryHint(SqlWalker::WALKER_HOOK_LIMIT_ID, $fieldName);

        return $queryBuilder;
    }
}
