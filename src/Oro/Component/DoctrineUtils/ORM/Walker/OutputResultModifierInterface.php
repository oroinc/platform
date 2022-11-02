<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\ORM\Query\AST;

/**
 * Doctrine Output result modifier that may be registered with DI tag and used by DecoratedSqlWalkerTrait.
 * Should not modify AST and only modify passed result.
 */
interface OutputResultModifierInterface
{
    public const HINT_RESULT_MODIFIERS = 'HINT_RESULT_MODIFIERS';

    /**
     * Initializes TreeWalker with important information about the ASTs to be walked.
     *
     * @param \Doctrine\ORM\AbstractQuery $query The parsed Query.
     * @param \Doctrine\ORM\Query\ParserResult $parserResult The result of the parsing process.
     * @param array $queryComponents The query components (symbol table).
     */
    public function __construct($query, $parserResult, array $queryComponents);

    /**
     * Returns internal queryComponents array.
     *
     * @return array
     */
    public function getQueryComponents();

    /**
     * Sets or overrides a query component for a given dql alias.
     *
     * @param string $dqlAlias The DQL alias.
     * @param array $queryComponent
     *
     * @return void
     */
    public function setQueryComponent(string $dqlAlias, array $queryComponent);

    /**
     * Walks down a SelectStatement AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SelectStatement $AST
     * @param string $result
     *
     * @return string The SQL.
     */
    public function walkSelectStatement(AST\SelectStatement $AST, string $result);

    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SelectClause $selectClause
     * @param string $result
     *
     * @return string The SQL.
     */
    public function walkSelectClause($selectClause, string $result);

    /**
     * Walks down a FromClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\FromClause $fromClause
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkFromClause($fromClause, string $result);

    /**
     * Walks down a FunctionNode AST node, thereby generating the appropriate SQL.
     *
     * @param AST\Functions\FunctionNode $function
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkFunction($function, string $result);

    /**
     * Walks down an OrderByClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\OrderByClause $orderByClause
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkOrderByClause($orderByClause, string $result);

    /**
     * Walks down an OrderByItem AST node, thereby generating the appropriate SQL.
     *
     * @param AST\OrderByItem $orderByItem
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkOrderByItem($orderByItem, string $result);

    /**
     * Walks down a HavingClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\HavingClause $havingClause
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkHavingClause($havingClause, string $result);

    /**
     * Walks down a Join AST node and creates the corresponding SQL.
     *
     * @param AST\Join $join
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkJoin($join, string $result);

    /**
     * Walks down a SelectExpression AST node and generates the corresponding SQL.
     *
     * @param AST\SelectExpression $selectExpression
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkSelectExpression($selectExpression, string $result);

    /**
     * Walks down a QuantifiedExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\QuantifiedExpression $qExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkQuantifiedExpression($qExpr, string $result);

    /**
     * Walks down a Subselect AST node, thereby generating the appropriate SQL.
     *
     * @param AST\Subselect $subselect
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkSubselect($subselect, string $result);

    /**
     * Walks down a SubselectFromClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SubselectFromClause $subselectFromClause
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkSubselectFromClause($subselectFromClause, string $result);

    /**
     * Walks down a SimpleSelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SimpleSelectClause $simpleSelectClause
     *
     * @return string The SQL.
     */
    public function walkSimpleSelectClause($simpleSelectClause, string $result);

    /**
     * Walks down a SimpleSelectExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SimpleSelectExpression $simpleSelectExpression
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkSimpleSelectExpression($simpleSelectExpression, string $result);

    /**
     * Walks down an AggregateExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\AggregateExpression $aggExpression
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkAggregateExpression($aggExpression, string $result);

    /**
     * Walks down a GroupByClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\GroupByClause $groupByClause
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkGroupByClause($groupByClause, string $result);

    /**
     * Walks down a GroupByItem AST node, thereby generating the appropriate SQL.
     *
     * @param AST\PathExpression|string $groupByItem
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkGroupByItem($groupByItem, string $result);

    /**
     * Walks down an UpdateStatement AST node, thereby generating the appropriate SQL.
     *
     * @param AST\UpdateStatement $AST
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkUpdateStatement(AST\UpdateStatement $AST, string $result);

    /**
     * Walks down a DeleteStatement AST node, thereby generating the appropriate SQL.
     *
     * @param AST\DeleteStatement $AST
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkDeleteStatement(AST\DeleteStatement $AST, string $result);

    /**
     * Walks down a DeleteClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\DeleteClause $deleteClause
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkDeleteClause(AST\DeleteClause $deleteClause, string $result);

    /**
     * Walks down an UpdateClause AST node, thereby generating the appropriate SQL.
     *
     * @param AST\UpdateClause $updateClause
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkUpdateClause($updateClause, string $result);

    /**
     * Walks down an UpdateItem AST node, thereby generating the appropriate SQL.
     *
     * @param AST\UpdateItem $updateItem
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkUpdateItem($updateItem, string $result);

    /**
     * Walks down a WhereClause AST node, thereby generating the appropriate SQL.
     * WhereClause or not, the appropriate discriminator sql is added.
     *
     * @param AST\WhereClause $whereClause
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkWhereClause($whereClause, string $result);

    /**
     * Walk down a ConditionalExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ConditionalExpression $condExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkConditionalExpression($condExpr, string $result);

    /**
     * Walks down a ConditionalTerm AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ConditionalTerm $condTerm
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkConditionalTerm($condTerm, string $result);

    /**
     * Walks down a ConditionalFactor AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ConditionalFactor $factor
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkConditionalFactor($factor, string $result);

    /**
     * Walks down a ConditionalPrimary AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ConditionalPrimary $primary
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkConditionalPrimary($primary, string $result);

    /**
     * Walks down an ExistsExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ExistsExpression $existsExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkExistsExpression($existsExpr, string $result);

    /**
     * Walks down a CollectionMemberExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\CollectionMemberExpression $collMemberExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkCollectionMemberExpression($collMemberExpr, string $result);

    /**
     * Walks down an EmptyCollectionComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\EmptyCollectionComparisonExpression $emptyCollCompExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr, string $result);

    /**
     * Walks down a NullComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\NullComparisonExpression $nullCompExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkNullComparisonExpression($nullCompExpr, string $result);

    /**
     * Walks down an InExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\InExpression $inExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkInExpression($inExpr, string $result);

    /**
     * Walks down an InstanceOfExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\InstanceOfExpression $instanceOfExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkInstanceOfExpression($instanceOfExpr, string $result);

    /**
     * Walks down a literal that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $literal
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkLiteral($literal, string $result);

    /**
     * Walks down a BetweenExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\BetweenExpression $betweenExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkBetweenExpression($betweenExpr, string $result);

    /**
     * Walks down a LikeExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\LikeExpression $likeExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkLikeExpression($likeExpr, string $result);

    /**
     * Walks down a StateFieldPathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\PathExpression $stateFieldPathExpression
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkStateFieldPathExpression($stateFieldPathExpression, string $result);

    /**
     * Walks down a ComparisonExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ComparisonExpression $compExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkComparisonExpression($compExpr, string $result);

    /**
     * Walks down an InputParameter AST node, thereby generating the appropriate SQL.
     *
     * @param AST\InputParameter $inputParam
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkInputParameter($inputParam, string $result);

    /**
     * Walks down an ArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\ArithmeticExpression $arithmeticExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkArithmeticExpression($arithmeticExpr, string $result);

    /**
     * Walks down an ArithmeticTerm AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $term
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkArithmeticTerm($term, string $result);

    /**
     * Walks down a StringPrimary that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $stringPrimary
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkStringPrimary($stringPrimary, string $result);

    /**
     * Walks down an ArithmeticFactor that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $factor
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkArithmeticFactor($factor, string $result);

    /**
     * Walks down an SimpleArithmeticExpression AST node, thereby generating the appropriate SQL.
     *
     * @param AST\SimpleArithmeticExpression $simpleArithmeticExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpr, string $result);

    /**
     * Walks down a PathExpression AST node, thereby generating the appropriate SQL.
     *
     * @param mixed $pathExpr
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkPathExpression($pathExpr, string $result);

    /**
     * Walks down a ResultVariable that represents an AST node, thereby generating the appropriate SQL.
     *
     * @param string $resultVariable
     *
     * @param string $result
     * @return string The SQL.
     */
    public function walkResultVariable($resultVariable, string $result);
}
