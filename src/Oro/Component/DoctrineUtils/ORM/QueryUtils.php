<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

/**
 * @deprecated since 2.3. Use QueryUtil, QueryBuilderUtil, ResultSetMappingUtil or DqlUtil instead
 */
class QueryUtils
{
    const IN         = QueryBuilderUtil::IN;
    const IN_BETWEEN = QueryBuilderUtil::IN_BETWEEN;

    /**
     * Makes full clone of the given query, including its parameters and hints
     *
     * @param Query $query
     *
     * @return Query
     */
    public static function cloneQuery(Query $query)
    {
        return QueryUtil::cloneQuery($query);
    }

    /**
     * Adds a custom tree walker to the given query.
     * Do nothing if the query already has the given walker.
     *
     * @param Query  $query       The query
     * @param string $walkerClass The FQCN of the tree walker
     *
     * @return bool TRUE if the walker was added; otherwise, FALSE
     */
    public static function addTreeWalker(Query $query, $walkerClass)
    {
        return QueryUtil::addTreeWalker($query, $walkerClass);
    }

    /**
     * @param Query $query
     * @param array $paramMappings
     *
     * @return array
     *
     * @throws QueryException
     */
    public static function processParameterMappings(Query $query, $paramMappings)
    {
        return QueryUtil::processParameterMappings($query, $paramMappings);
    }

    /**
     * @param AbstractPlatform $platform
     *
     * @return ResultSetMapping
     */
    public static function createResultSetMapping(AbstractPlatform $platform)
    {
        return ResultSetMappingUtil::createResultSetMapping($platform);
    }

    /**
     * @param ResultSetMapping $mapping
     * @param string           $alias
     *
     * @return string
     *
     * @throws QueryException
     */
    public static function getColumnNameByAlias(ResultSetMapping $mapping, $alias)
    {
        return ResultSetMappingUtil::getColumnNameByAlias($mapping, $alias);
    }

    /**
     * Returns an expression in SELECT clause by its alias
     *
     * @param QueryBuilder $qb
     * @param string       $alias An alias of an expression in SELECT clause
     *
     * @return string|null
     */
    public static function getSelectExprByAlias(QueryBuilder $qb, $alias)
    {
        return QueryBuilderUtil::getSelectExprByAlias($qb, $alias);
    }

    /**
     * @param Query                   $query
     * @param Query\ParserResult|null $parsedQuery
     *
     * @return string
     *
     * @throws QueryException
     */
    public static function getExecutableSql(Query $query, Query\ParserResult $parsedQuery = null)
    {
        return QueryUtil::getExecutableSql($query, $parsedQuery);
    }

    /**
     * @param Query $query
     *
     * @return Query\ParserResult
     */
    public static function parseQuery(Query $query)
    {
        return QueryUtil::parseQuery($query);
    }

    /**
     * Builds CONCAT(...) DQL expression
     *
     * @param string[] $parts
     *
     * @return string
     */
    public static function buildConcatExpr(array $parts)
    {
        return DqlUtil::buildConcatExpr($parts);
    }

    /**
     * Gets the root entity alias of the query.
     *
     * @param QueryBuilder $qb             The query builder
     * @param bool         $throwException Whether to throw exception in case the query does not have a root alias
     *
     * @return string|null
     *
     * @throws QueryException
     */
    public static function getSingleRootAlias(QueryBuilder $qb, $throwException = true)
    {
        return QueryBuilderUtil::getSingleRootAlias($qb, $throwException);
    }

    /**
     * Calculates the page offset
     *
     * @param int $page  The page number
     * @param int $limit The maximum number of items per page
     *
     * @return int
     */
    public static function getPageOffset($page, $limit)
    {
        return QueryBuilderUtil::getPageOffset($page, $limit);
    }

    /**
     * Applies the given joins for the query builder
     *
     * @param QueryBuilder $qb
     * @param array|null   $joins
     */
    public static function applyJoins(QueryBuilder $qb, $joins)
    {
        QueryBuilderUtil::applyJoins($qb, $joins);
    }

    /**
     * Checks the given criteria and converts them to Criteria object if needed
     *
     * @param Criteria|array|null $criteria
     *
     * @return Criteria
     */
    public static function normalizeCriteria($criteria)
    {
        return QueryBuilderUtil::normalizeCriteria($criteria);
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $field
     * @param int[]        $values
     */
    public static function applyOptimizedIn(QueryBuilder $qb, $field, array $values)
    {
        QueryBuilderUtil::applyOptimizedIn($qb, $field, $values);
    }

    /**
     * @param int[] $values
     *
     * @return array
     */
    public static function optimizeIntegerValues(array $values)
    {
        return QueryBuilderUtil::optimizeIntegerValues($values);
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    public static function generateParameterName($prefix)
    {
        return QueryBuilderUtil::generateParameterName($prefix);
    }

    /**
     * Removes unused parameters from query builder
     *
     * @param QueryBuilder $qb
     */
    public static function removeUnusedParameters(QueryBuilder $qb)
    {
        QueryBuilderUtil::removeUnusedParameters($qb);
    }

    /**
     * Returns TRUE if $dql contains usage of parameter with $parameterName
     *
     * @param string $dql
     * @param string $parameterName
     *
     * @return bool
     */
    public static function dqlContainsParameter($dql, $parameterName)
    {
        return DqlUtil::hasParameter($dql, $parameterName);
    }

    /**
     * @param string $dql
     *
     * @return array
     */
    public static function getDqlAliases($dql)
    {
        return DqlUtil::getAliases($dql);
    }

    /**
     * @param string $dql
     * @param array $replacements
     *
     * @return string
     */
    public static function replaceDqlAliases($dql, array $replacements)
    {
        return DqlUtil::replaceAliases($dql, $replacements);
    }

    /**
     * @param QueryBuilder $qb
     * @param Expr\Join $join
     *
     * @return string
     */
    public static function getJoinClass(QueryBuilder $qb, Expr\Join $join)
    {
        return QueryBuilderUtil::getJoinClass($qb, $join);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     *
     * @return Expr\Join|null
     */
    public static function findJoinByAlias(QueryBuilder $qb, $alias)
    {
        return QueryBuilderUtil::findJoinByAlias($qb, $alias);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     *
     * @return bool
     */
    public static function isToOne(QueryBuilder $qb, $alias)
    {
        return QueryBuilderUtil::isToOne($qb, $alias);
    }
}
