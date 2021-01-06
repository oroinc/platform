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
        $groupBy = implode(', ', self::getSelectFieldFromGroupBy($ds->getQueryBuilder()));
        $subQuery
            ->resetDQLPart('orderBy')
            ->select($fieldExpr)
            ->andWhere(sprintf('%1$s = %1$s', $fieldExpr));

        if ($groupBy) {
            // replace aliases from SELECT by expressions, since SELECT clause is changed, add current field
            $subQuery->groupBy(sprintf('%s, %s', $groupBy, $fieldExpr));
        }
        [$dql, $replacements] = self::createDqlWithReplacedAliases($ds, $subQuery, $filterName);
        [$fieldAlias, $field] = explode('.', $fieldExpr);
        $replacedFieldExpr = sprintf('%s.%s', $replacements[$fieldAlias], $field);
        $oldExpr = sprintf('%1$s = %1$s', $replacedFieldExpr);
        $newExpr = sprintf('%s = %s', $replacedFieldExpr, $fieldExpr);
        $dql = strtr($dql, [$oldExpr => $newExpr]);

        return [$dql, $subQuery->getParameters()];
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

    /**
     * @param QueryBuilder $qb
     *
     * @return string
     */
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

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string                     $column
     *
     * @return Expr\Join|null
     */
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

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string                     $column
     *
     * @return bool
     */
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
        if (strpos($groupByPart, ',') !== false) {
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
}
