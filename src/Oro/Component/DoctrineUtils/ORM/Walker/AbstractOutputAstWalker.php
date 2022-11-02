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

    /**
     * {@inheritDoc}
     */
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
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Gets the Connection used by the walker.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Gets the EntityManager used by the walker.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryComponent($dqlAlias)
    {
        return $this->queryComponents[$dqlAlias];
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryComponents()
    {
        return $this->queryComponents;
    }

    /**
     * {@inheritdoc}
     */
    public function setQueryComponent($dqlAlias, array $queryComponent)
    {
        $requiredKeys = ['metadata', 'parent', 'relation', 'map', 'nestingLevel', 'token'];

        if (array_diff($requiredKeys, array_keys($queryComponent))) {
            throw QueryException::invalidQueryComponent($dqlAlias);
        }

        $this->queryComponents[$dqlAlias] = $queryComponent;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkFunction($function)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByClause($orderByClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkHavingClause($havingClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkJoin($join)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectExpression($selectExpression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkQuantifiedExpression($qExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectExpression($simpleSelectExpression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkAggregateExpression($aggExpression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByClause($groupByClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByItem($groupByItem)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateClause($updateClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateItem($updateItem)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkWhereClause($whereClause)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalExpression($condExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalTerm($condTerm)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalFactor($factor)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalPrimary($primary)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkExistsExpression($existsExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkCollectionMemberExpression($collMemberExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparisonExpression($nullCompExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInExpression($inExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInstanceOfExpression($instanceOfExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral($literal)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkBetweenExpression($betweenExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkLikeExpression($likeExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression($compExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkInputParameter($inputParam)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticExpression($arithmeticExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticTerm($term)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkStringPrimary($stringPrimary)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticFactor($factor)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkPathExpression($pathExpr)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkResultVariable($resultVariable)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST)
    {
    }
}
