<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * An output result modifier implementation of the OutputResultModifierInterface interface.
 * The methods in this class are empty. ﻿This class exists as convenience for creating output result modifiers.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractOutputResultModifier implements OutputResultModifierInterface
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
     * Gets the information about a single query component.
     *
     * @param string $dqlAlias The DQL alias.
     *
     * @return array
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
    public function setQueryComponent(string $dqlAlias, array $queryComponent)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkFunction($function, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByClause($orderByClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkHavingClause($havingClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkJoin($join, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectExpression($selectExpression, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkQuantifiedExpression($qExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselectFromClause($subselectFromClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleSelectExpression($simpleSelectExpression, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkAggregateExpression($aggExpression, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByClause($groupByClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkGroupByItem($groupByItem, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateClause($updateClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkUpdateItem($updateItem, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkWhereClause($whereClause, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalExpression($condExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalTerm($condTerm, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalFactor($factor, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkConditionalPrimary($primary, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkExistsExpression($existsExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkCollectionMemberExpression($collMemberExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkNullComparisonExpression($nullCompExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkInExpression($inExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkInstanceOfExpression($instanceOfExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral($literal, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkBetweenExpression($betweenExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkLikeExpression($likeExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression($compExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkInputParameter($inputParam, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticExpression($arithmeticExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticTerm($term, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkStringPrimary($stringPrimary, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkArithmeticFactor($factor, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkPathExpression($pathExpr, string $result)
    {
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkResultVariable($resultVariable, string $result)
    {
        return $result;
    }
}
