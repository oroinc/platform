<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Component\DoctrineUtils\ORM\DqlUtil;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides a set of reusable static methods to help building an ORM queries for filters.
 */
class FilterOrmQueryUtil
{
    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param QueryBuilder               $subQuery
     * @param string                     $fieldExpr
     * @param string                     $filterName
     *
     * @return array [DQL, parameters]
     */
    public static function getSubQueryExpressionWithParameters(
        OrmFilterDatasourceAdapter $ds,
        QueryBuilder $subQuery,
        string $fieldExpr,
        string $filterName
    ): array {
        QueryBuilderUtil::checkField($fieldExpr);
        $subQuery
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select($fieldExpr)
            ->andWhere(sprintf('%1$s = %1$s', $fieldExpr));

        self::processSubQueryExpressionGroupBy($ds, $subQuery, $fieldExpr);
        [$dql, $replacements] = self::createDqlWithReplacedAliases($ds, $subQuery, $filterName);
        [$fieldAlias, $field] = explode('.', $fieldExpr);
        $replacedFieldExpr = sprintf('%s.%s', $replacements[$fieldAlias], $field);
        $oldExpr = sprintf('%1$s = %1$s', $replacedFieldExpr);
        $newExpr = sprintf('%s = %s', $replacedFieldExpr, $fieldExpr);
        $dql = strtr($dql, [$oldExpr => $newExpr]);

        return [$dql, $subQuery->getParameters()];
    }

    public static function containGroupByFunctionAndHaving(
        OrmFilterDatasourceAdapter $ds
    ): bool {
        $qb = $ds->getQueryBuilder();
        if (!$qb->getDQLPart('having')) {
            return false;
        }

        foreach (self::getSelectFieldFromGroupBy($qb) as $groupByField) {
            if (str_contains($groupByField, '(')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param QueryBuilder               $qb
     * @param string                     $filterName
     *
     * @return array [DQL, replaced aliases]
     */
    public static function createDqlWithReplacedAliases(
        OrmFilterDatasourceAdapter $ds,
        QueryBuilder $qb,
        string $filterName
    ): array {
        $replacements = array_map(
            function ($alias) use ($ds, $filterName) {
                return [
                    $alias,
                    $ds->generateParameterName($filterName),
                ];
            },
            DqlUtil::getAliases($qb->getDQL())
        );

        return [
            DqlUtil::replaceAliases($qb->getDQL(), $replacements),
            array_combine(array_column($replacements, 0), array_column($replacements, 1))
        ];
    }

    public static function getSingleIdentifierFieldExpr(QueryBuilder $qb): string
    {
        $entities = $qb->getRootEntities();
        $idField = $qb
            ->getEntityManager()
            ->getClassMetadata(reset($entities))
            ->getSingleIdentifierFieldName();
        $rootAliases = $qb->getRootAliases();

        return sprintf('%s.%s', reset($rootAliases), $idField);
    }

    public static function findRelatedJoinByColumn(
        OrmFilterDatasourceAdapter $ds,
        string $column
    ): ?Expr\Join {
        if (self::isToOneColumn($ds, $column)) {
            return null;
        }

        [$alias] = explode('.', $column);

        return QueryBuilderUtil::findJoinByAlias($ds->getQueryBuilder(), $alias);
    }

    public static function isToOneColumn(OrmFilterDatasourceAdapter $ds, string $column): bool
    {
        [$joinAlias] = explode('.', $column);

        return QueryBuilderUtil::isToOne($ds->getQueryBuilder(), $joinAlias);
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return array
     */
    private static function getSelectFieldFromGroupBy(QueryBuilder $qb)
    {
        $groupBy = $qb->getDQLPart('groupBy');

        $expressions = [];
        foreach ($groupBy as $groupByPart) {
            foreach ($groupByPart->getParts() as $part) {
                $expressions = array_merge($expressions, self::getSelectFieldFromGroupByPart($qb, $part));
            }
        }

        $fields = [];
        foreach ($expressions as $expression) {
            $fields[] = QueryBuilderUtil::getSelectExprByAlias($qb, $expression) ?: $expression;
        }

        return $fields;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $groupByPart
     *
     * @return array
     */
    private static function getSelectFieldFromGroupByPart(QueryBuilder $qb, $groupByPart)
    {
        $expressions = [];
        if (str_contains($groupByPart, ',')) {
            $groupByParts = explode(',', $groupByPart);
            foreach ($groupByParts as $part) {
                $expressions = array_merge($expressions, self::getSelectFieldFromGroupByPart($qb, $part));
            }
        } else {
            $trimmedGroupByPart = trim($groupByPart);
            $expr = QueryBuilderUtil::getSelectExprByAlias($qb, $groupByPart);
            $expressions[] = $expr ?: $trimmedGroupByPart;
        }

        return $expressions;
    }

    private static function processSubQueryExpressionGroupBy(
        OrmFilterDatasourceAdapter $ds,
        QueryBuilder $subQuery,
        string $fieldExpr
    ): void {
        // No need to add group by to sub-query if there is no additional having conditions applied
        if ($ds->getQueryBuilder()->getDQLPart('having')
            && $groupByFields = self::getSelectFieldFromGroupBy($ds->getQueryBuilder())
        ) {
            $subQuery->addGroupBy(implode(', ', $groupByFields));
            $subQuery->addGroupBy($fieldExpr);
        }
    }
}
