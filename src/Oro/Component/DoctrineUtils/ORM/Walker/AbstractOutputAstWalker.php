<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * An output AST walker implementation of the OutputAstWalkerInterface interface.
 * The methods in this class are empty. ﻿This class exists as convenience for creating output AST walkers.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractOutputAstWalker implements OutputAstWalkerInterface
{
    /**
     * @var ResultSetMapping
     */
    protected $rsm;

    /**
     * @var ParserResult
     */
    protected $parserResult;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var \Doctrine\ORM\AbstractQuery
     */
    protected $query;

    /**
     * Map of all components/classes that appear in the DQL query.
     *
     * @var array
     */
    protected $queryComponents;

    /**
     * The database platform abstraction.
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;

    /**
     * The quote strategy.
     *
     * @var \Doctrine\ORM\Mapping\QuoteStrategy
     */
    protected $quoteStrategy;

    public function __construct($query, $parserResult, array $queryComponents)
    {
        $this->query = $query;
        $this->parserResult = $parserResult;
        $this->queryComponents = $queryComponents;
        $this->rsm = $parserResult->getResultSetMapping();
        $this->em = $query->getEntityManager();
        $this->conn = $this->em->getConnection();
        $this->platform = $this->conn->getDatabasePlatform();
        $this->quoteStrategy = $this->em->getConfiguration()->getQuoteStrategy();
    }

    /**
     * Gets the Query instance used by the walker.
     *
     * @return AbstractQuery.
     */
    #[\Override]
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Gets the Connection used by the walker.
     *
     * @return \Doctrine\DBAL\Connection
     */
    #[\Override]
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Gets the EntityManager used by the walker.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    #[\Override]
    public function getEntityManager()
    {
        return $this->em;
    }

    #[\Override]
    public function getQueryComponent($dqlAlias)
    {
        return $this->queryComponents[$dqlAlias];
    }

    #[\Override]
    public function getQueryComponents()
    {
        return $this->queryComponents;
    }

    #[\Override]
    public function setQueryComponent($dqlAlias, array $queryComponent)
    {
        $requiredKeys = ['metadata', 'parent', 'relation', 'map', 'nestingLevel', 'token'];

        if (array_diff($requiredKeys, array_keys($queryComponent))) {
            throw QueryException::invalidQueryComponent($dqlAlias);
        }

        $this->queryComponents[$dqlAlias] = $queryComponent;
    }

    #[\Override]
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
    }

    #[\Override]
    public function walkSelectClause($selectClause)
    {
    }

    #[\Override]
    public function walkFromClause($fromClause)
    {
    }

    #[\Override]
    public function walkFunction($function)
    {
    }

    #[\Override]
    public function walkOrderByClause($orderByClause)
    {
    }

    #[\Override]
    public function walkOrderByItem($orderByItem)
    {
    }

    #[\Override]
    public function walkHavingClause($havingClause)
    {
    }

    #[\Override]
    public function walkJoin($join)
    {
    }

    #[\Override]
    public function walkSelectExpression($selectExpression)
    {
    }

    #[\Override]
    public function walkQuantifiedExpression($qExpr)
    {
    }

    #[\Override]
    public function walkSubselect($subselect)
    {
    }

    #[\Override]
    public function walkSubselectFromClause($subselectFromClause)
    {
    }

    #[\Override]
    public function walkSimpleSelectClause($simpleSelectClause)
    {
    }

    #[\Override]
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
    }

    #[\Override]
    public function walkAggregateExpression($aggExpression)
    {
    }

    #[\Override]
    public function walkGroupByClause($groupByClause)
    {
    }

    #[\Override]
    public function walkGroupByItem($groupByItem)
    {
    }

    #[\Override]
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
    }

    #[\Override]
    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
    }

    #[\Override]
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
    }

    #[\Override]
    public function walkUpdateClause($updateClause)
    {
    }

    #[\Override]
    public function walkUpdateItem($updateItem)
    {
    }

    #[\Override]
    public function walkWhereClause($whereClause)
    {
    }

    #[\Override]
    public function walkConditionalExpression($condExpr)
    {
    }

    #[\Override]
    public function walkConditionalTerm($condTerm)
    {
    }

    #[\Override]
    public function walkConditionalFactor($factor)
    {
    }

    #[\Override]
    public function walkConditionalPrimary($primary)
    {
    }

    #[\Override]
    public function walkExistsExpression($existsExpr)
    {
    }

    #[\Override]
    public function walkCollectionMemberExpression($collMemberExpr)
    {
    }

    #[\Override]
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
    }

    #[\Override]
    public function walkNullComparisonExpression($nullCompExpr)
    {
    }

    #[\Override]
    public function walkInExpression($inExpr)
    {
    }

    #[\Override]
    public function walkInstanceOfExpression($instanceOfExpr)
    {
    }

    #[\Override]
    public function walkLiteral($literal)
    {
    }

    #[\Override]
    public function walkBetweenExpression($betweenExpr)
    {
    }

    #[\Override]
    public function walkLikeExpression($likeExpr)
    {
    }

    #[\Override]
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
    }

    #[\Override]
    public function walkComparisonExpression($compExpr)
    {
    }

    #[\Override]
    public function walkInputParameter($inputParam)
    {
    }

    #[\Override]
    public function walkArithmeticExpression($arithmeticExpr)
    {
    }

    #[\Override]
    public function walkArithmeticTerm($term)
    {
    }

    #[\Override]
    public function walkStringPrimary($stringPrimary)
    {
    }

    #[\Override]
    public function walkArithmeticFactor($factor)
    {
    }

    #[\Override]
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
    }

    #[\Override]
    public function walkPathExpression($pathExpr)
    {
    }

    #[\Override]
    public function walkResultVariable($resultVariable)
    {
    }

    #[\Override]
    public function getExecutor($AST)
    {
    }
}
