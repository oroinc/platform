<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Converts a tokenized search query (TokenStream object) to Query object.
 * To tokenize a search query use Lexer class.
 * @see \Oro\Bundle\SearchBundle\Query\Expression\Lexer
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Parser
{
    /** @var TokenStream */
    protected $stream;

    /** @var Query */
    protected $query;

    /** @var FieldResolverInterface */
    protected $fieldResolver;

    /**
     * @param TokenStream                 $stream
     * @param Query|null                  $query
     * @param FieldResolverInterface|null $fieldResolver
     * @param string|null                 $keyword
     *
     * @return Query
     *
     * @throws ExpressionSyntaxError
     */
    public function parse(
        TokenStream $stream,
        Query $query = null,
        FieldResolverInterface $fieldResolver = null,
        $keyword = null
    ) {
        if (null === $query) {
            $query = new Query();
            $query->from(['*']);
        }
        $this->stream = $stream;
        $this->query = $query;
        $this->fieldResolver = $fieldResolver ?? new FieldResolver();
        try {
            if ($keyword) {
                $this->parseExpression($keyword);
            } else {
                while (!$this->stream->isEOF()
                    && $this->stream->current->test(Token::KEYWORD_TYPE)
                    && in_array($this->stream->current->value, TokenInfo::getKeywords(), true)
                ) {
                    $this->parseExpression($this->stream->current->value);
                }
            }
            if (!$stream->isEOF()) {
                throw new ExpressionSyntaxError(
                    sprintf('Unexpected token "%s", value "%s"', $stream->current->type, $stream->current->value),
                    $stream->current->cursor
                );
            }
        } finally {
            $this->stream = null;
            $this->query = null;
            $this->fieldResolver = null;
        }

        return $query;
    }

    /**
     * @param string $keyword
     */
    protected function parseExpression($keyword)
    {
        switch ($keyword) {
            case Query::KEYWORD_SELECT:
                $this->parseSelectExpression();
                break;
            case Query::KEYWORD_FROM:
                $this->parseFromExpression();
                break;
            case Query::KEYWORD_WHERE:
                $this->parseWhereExpression();
                break;
            case Query::KEYWORD_AGGREGATE:
                $this->parseAggregateExpression();
                break;
            case Query::KEYWORD_OFFSET:
                $this->parseOffsetExpression();
                break;
            case Query::KEYWORD_MAX_RESULTS:
                $this->parseMaxResultsExpression();
                break;
            case Query::KEYWORD_ORDER_BY:
                $this->parseOrderByExpression();
                break;
            default:
                throw new ExpressionSyntaxError(
                    sprintf(
                        'Unexpected token "%s", value "%s"',
                        $this->stream->current->type,
                        $this->stream->current->value
                    ),
                    $this->stream->current->cursor
                );
        }
    }

    /**
     *  Parse select statement of expression and fills Query's select.
     */
    protected function parseSelectExpression()
    {
        $this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_SELECT);
        switch (true) {
            // if got string token after "select" - pass it directly into Query
            case (true === $this->stream->current->test(Token::STRING_TYPE)):
                $fieldDeclaration = $this->stream->current->value;
                $this->stream->next();

                if ($this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_AS, null, false)) {
                    $aliasName = $this->stream->current->value;
                    $fieldDeclaration .= ' ' . Query::KEYWORD_AS . ' ' . $aliasName;
                    $this->stream->next();
                }

                $this->query->select($fieldDeclaration);
                break;

                // if got opening bracket (punctuation '(') - collect all arguments
            case (true === $this->stream->current->test(Token::PUNCTUATION_TYPE, '(')):
                $this->query->select($this->parseSelectKeywordArguments());
                break;

            default:
                throw new ExpressionSyntaxError(
                    'Wrong "select" statement of the expression.',
                    $this->stream->current->cursor
                );
        }
    }

    /**
     *  Parse from statement of expression and fills Query's from.
     */
    protected function parseFromExpression()
    {
        $this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_FROM);
        switch (true) {
            // if got string token after "from" - pass it directly into Query
            case (true === $this->stream->current->test(Token::STRING_TYPE)):
                $this->query->from($this->stream->current->value);
                $this->stream->next();
                break;

                // if got operator (only '*' is supported in from statement)
            case (true === $this->stream->current->test(Token::OPERATOR_TYPE, '*')):
                $this->query->from(['*']);
                $this->stream->next();
                break;

                // if got opening bracket (punctuation '(') - collect all arguments
            case (true === $this->stream->current->test(Token::PUNCTUATION_TYPE, '(')):
                $this->query->from($this->parseArguments());
                break;

            default:
                throw new ExpressionSyntaxError(
                    'Wrong "from" statement of the expression.',
                    $this->stream->current->cursor
                );
        }
    }

    /**
     * Parse where statement of expression and fills Criteria expressions.
     */
    protected function parseWhereExpression()
    {
        /** @var Token $whereToken */
        $this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_WHERE, null, false);

        $exit = false;
        $previousType = '';
        while (!$this->stream->isEOF() && !$exit) {
            /** @var Token $token */
            $token = $this->stream->current;
            list($exit, $previousType) = $this->parseToken($token, $previousType);
        }
    }

    /**
     * Parse aggregate expression from string query
     */
    protected function parseAggregateExpression()
    {
        // skip AGGREGATE keyword
        $this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_AGGREGATE);

        // parse field name
        $fieldTypeToken = $this->stream->expect(Token::STRING_TYPE, TokenInfo::getTypes(), null, false);
        $fieldName = $this->stream->expect(Token::STRING_TYPE, null, 'Aggregating field is expected')->value;
        $fieldType = $fieldTypeToken ? $fieldTypeToken->value : $this->fieldResolver->resolveFieldType($fieldName);
        $fieldName = Criteria::implodeFieldTypeName(
            $fieldType,
            $this->fieldResolver->resolveFieldName($fieldName)
        );

        // parse function
        $functionToken = $this->stream->expect(
            Token::STRING_TYPE,
            TokenInfo::getAggregatingFunctions(),
            'Aggregating function expected'
        );
        $function = $functionToken->value;

        if (!in_array($function, TokenInfo::getAggregatingFunctionsForType($fieldType), true)) {
            throw new ExpressionSyntaxError(
                sprintf('Unsupported aggregating function "%s" for field type "%s"', $function, $fieldType),
                $this->stream->current->cursor
            );
        }

        // skip optional AS keyword
        if ($this->stream->current->test(Token::KEYWORD_TYPE, Query::KEYWORD_AS)) {
            $this->stream->next();
        }

        // parse aggregating name
        $nameToken = $this->stream->expect(Token::STRING_TYPE, null, 'Aggregating name is expected');
        $name = $nameToken->value;

        $this->query->addAggregate($name, $fieldName, $function);
    }

    /**
     * Parse order_by statement of expression and fills Criteria orderings.
     */
    protected function parseOrderByExpression()
    {
        /** @var Criteria $criteria */
        $criteria = $this->query->getCriteria();

        /** @var Token $orderByToken */
        $orderByToken = $this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_ORDER_BY);

        $from = $this->query->getFrom();
        if (count($from) > 1 || $from[0] === '*') {
            throw new ExpressionSyntaxError(
                sprintf(
                    'Order By expression is allowed only for searching by single entity. Token "%s", value "%s"',
                    $orderByToken->type,
                    $orderByToken->value
                ),
                $orderByToken->cursor
            );
        }

        $orderFieldType = $this->stream->expect(Token::STRING_TYPE, TokenInfo::getTypes(), null, false);
        $orderFieldName = $this->stream->expect(Token::STRING_TYPE, null, 'Ordering field name is expected')->value;
        $orderFieldName = Criteria::implodeFieldTypeName(
            $orderFieldType ? $orderFieldType->value : $this->fieldResolver->resolveFieldType($orderFieldName),
            $this->fieldResolver->resolveFieldName($orderFieldName)
        );

        $orderDirection = Criteria::ASC;
        if (!$this->stream->isEOF() && $this->stream->current->test(Token::STRING_TYPE)) {
            $orderDirectionToken = $this->stream->expect(
                Token::STRING_TYPE,
                TokenInfo::getOrderDirections(),
                null,
                false
            );
            if ($orderDirectionToken) {
                $orderDirection = $orderDirectionToken->value;
            }
        }

        $criteria->orderBy([$orderFieldName => $orderDirection]);
    }

    /**
     * Parse offset statement of expression.
     */
    protected function parseOffsetExpression()
    {
        $this->stream->next();
        /** @var Token $token */
        $token = $this->stream->current;
        if ($token->test(Token::NUMBER_TYPE)) {
            $this->query->getCriteria()->setFirstResult($token->value);
            $this->stream->next();
        } else {
            throw new ExpressionSyntaxError(
                sprintf('Unexpected token "%s", value "%s" in offset statements', $token->type, $token->value),
                $token->cursor
            );
        }
    }

    /**
     * Parse max_results statement of expression.
     */
    protected function parseMaxResultsExpression()
    {
        $this->stream->next();
        /** @var Token $token */
        $token = $this->stream->current;
        if ($token->test(Token::NUMBER_TYPE)) {
            $this->query->getCriteria()->setMaxResults($token->value);
            $this->stream->next();
        } else {
            throw new ExpressionSyntaxError(
                sprintf('Unexpected token "%s", value "%s" in offset statements', $token->type, $token->value),
                $token->cursor
            );
        }
    }

    /**
     * @param null|string $whereType can be 'and' | 'or' | 'null'
     *
     * @return array
     *     key 0 -> type
     *     key 1 -> expression
     */
    protected function parseSimpleCondition($whereType = null)
    {
        $expr = Criteria::expr();

        if (null === $whereType) {
            $whereType = 'andWhere';
        } else {
            $whereType = sprintf(
                '%sWhere',
                $this->stream->expect(Token::OPERATOR_TYPE, [Query::KEYWORD_AND, Query::KEYWORD_OR])->value
            );
        }

        if ($this->stream->current->test(Token::PUNCTUATION_TYPE)) {
            return [$whereType, $this->parseCompositeCondition()];
        }

        $fieldTypeToken = $this->stream->expect(Token::STRING_TYPE, null, null, false);
        $fieldNameToken = null;
        if ($fieldTypeToken && Token::STRING_TYPE === $this->stream->current->type) {
            $fieldNameToken = $this->stream->expect(Token::STRING_TYPE, null, null, false);
        }

        if (!$fieldNameToken) {
            /**
             * If field type is not specified we got field name in first expect ($fieldTypeToken)
             */
            $fieldType = $this->fieldResolver->resolveFieldType($fieldTypeToken->value);
            $fieldName = Criteria::implodeFieldTypeName(
                $fieldType,
                $this->fieldResolver->resolveFieldName($fieldTypeToken->value)
            );
        } else {
            $fieldType = $fieldTypeToken->value;
            $fieldName = Criteria::implodeFieldTypeName(
                $fieldType,
                $this->fieldResolver->resolveFieldName($fieldNameToken->value)
            );
        }

        if (!in_array($fieldType, TokenInfo::getTypes(), true)) {
            throw new ExpressionSyntaxError(
                sprintf('Unknown field type "%s"', $fieldType),
                $this->stream->current->cursor
            );
        }

        /** @var Token $operatorToken */
        $operatorToken = $this->stream->expect(
            Token::OPERATOR_TYPE,
            TokenInfo::getOperatorsForType($fieldType),
            'Not allowed operator'
        );

        $expression = $this->getComparisonForInAndExistsOperators($operatorToken, $expr, $fieldName);
        if (!$expression) {
            $expression = $this->getComparisonForOtherOperators($operatorToken, $expr, $fieldName);
        }

        return [$whereType, $expression];
    }

    /**
     * @return \Doctrine\Common\Collections\ExpressionBuilder|mixed
     */
    protected function parseCompositeCondition()
    {
        $expressions = [];

        $this->stream->next();

        while (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ')')) {
            $type = null;
            if ($this->stream->current->test(Token::OPERATOR_TYPE)) {
                $type = $this->stream->current->value;
            }

            list($typeX, $expression) = $this->parseSimpleCondition($type);
            $expressions[] = [
                'type' => $typeX,
                'expr' => $expression
            ];
        }

        $expr = Criteria::expr();
        if ($expressions) {
            $expressions = array_reverse($expressions);

            $typeX = $expressions[0]['type'];
            $expressions = array_map(
                function ($item) use ($typeX, $expressions) {
                    if ($item['type'] !== $typeX && $item != end($expressions)) {
                        throw new ExpressionSyntaxError(
                            'Syntax error. Composite operators of different types are not allowed on single level.',
                            $this->stream->current->cursor
                        );
                    }
                    return $item['expr'];
                },
                $expressions
            );

            $expr = call_user_func_array([$expr, str_replace('Where', 'X', $typeX)], $expressions);
        } else {
            throw new ExpressionSyntaxError(
                'Syntax error in composite expression.',
                $this->stream->current->cursor
            );
        }

        $this->stream->next();

        return $expr;
    }

    /**
     * @return array
     */
    public function parseArguments()
    {
        $args = [];
        $this->stream->expect(
            Token::PUNCTUATION_TYPE,
            '(',
            'A list of arguments must begin with an opening parenthesis'
        );
        while (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ')')) {
            if (!empty($args)) {
                $this->stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');
            }

            $args[] = $this->stream->current->value;
            $this->stream->next();
        }
        $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return $args;
    }

    /**
     * @return array
     */
    public function parseSelectKeywordArguments()
    {
        $args = [];
        $this->stream->expect(
            Token::PUNCTUATION_TYPE,
            '(',
            'A list of arguments must begin with an opening parenthesis'
        );
        while (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ')')) {
            if (!empty($args)) {
                $this->stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');
            }

            $fieldDeclaration = $this->stream->current->value;
            $this->stream->next();

            if ($this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_AS, null, false)) {
                $aliasName = $this->stream->current->value;
                $fieldDeclaration .= ' ' . Query::KEYWORD_AS . ' ' . $aliasName;
                $this->stream->next();
            }

            $args[] = $fieldDeclaration;
        }

        $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return $args;
    }

    /**
     * @param Token $token
     * @param string $previousType
     *
     * @return array [$exit, $tokenType]
     */
    private function parseToken(Token $token, string $previousType)
    {
        $exit = false;
        switch ($token->type) {
            case Token::PUNCTUATION_TYPE && $token->test(Token::PUNCTUATION_TYPE, '('):
                /** @var CompositeExpression $expr */
                $expr = $this->parseCompositeCondition();
                if ($expr instanceof CompositeExpression) {
                    $this->query->getCriteria()->{strtolower($expr->getType()) . 'Where'}($expr);
                }
                break;
            case Token::STRING_TYPE:
                if ($previousType === Token::STRING_TYPE) {
                    throw new ExpressionSyntaxError(
                        sprintf('Unexpected string "%s" in where statement', $this->stream->current->value),
                        $this->stream->current->cursor
                    );
                }
                list($type, $expr) = $this->parseSimpleCondition();
                $this->query->getCriteria()->{$type}($expr);
                break;
            case Token::OPERATOR_TYPE && in_array($token->value, TokenInfo::getLogicalOperators(), true):
                list($type, $expr) = $this->parseSimpleCondition($token->value);
                $this->query->getCriteria()->{$type}($expr);
                break;
            case Token::KEYWORD_TYPE:
                $exit = true;
                break;
            default:
                throw new ExpressionSyntaxError(
                    sprintf('Unexpected token "%s" in where statement', $this->stream->current->type),
                    $this->stream->current->cursor
                );
        }

        return [$exit, $token->type];
    }

    /**
     * @param Token $operatorToken
     * @param ExpressionBuilder $expr
     * @param string $fieldName
     * @return Comparison
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getComparisonForOtherOperators(Token $operatorToken, ExpressionBuilder $expr, $fieldName)
    {
        switch ($operatorToken->value) {
            case Query::OPERATOR_CONTAINS:
                $expr = $expr->contains($fieldName, $this->stream->current->value);
                break;
            case Query::OPERATOR_NOT_CONTAINS:
                $expr = $expr->notContains($fieldName, $this->stream->current->value);
                break;

            case Query::OPERATOR_LIKE:
                $expr = $expr->like($fieldName, $this->stream->current->value);
                break;

            case Query::OPERATOR_NOT_LIKE:
                $expr = $expr->notLike($fieldName, $this->stream->current->value);
                break;

            case Query::OPERATOR_EQUALS:
                $expr = $expr->eq($fieldName, $this->stream->current->value);
                break;
            case Query::OPERATOR_NOT_EQUALS:
                $expr = $expr->neq($fieldName, $this->stream->current->value);
                break;

            case Query::OPERATOR_GREATER_THAN:
                $expr = $expr->gt($fieldName, $this->stream->current->value);
                break;
            case Query::OPERATOR_GREATER_THAN_EQUALS:
                $expr = $expr->gte($fieldName, $this->stream->current->value);
                break;

            case Query::OPERATOR_LESS_THAN:
                $expr = $expr->lt($fieldName, $this->stream->current->value);
                break;
            case Query::OPERATOR_LESS_THAN_EQUALS:
                $expr = $expr->lte($fieldName, $this->stream->current->value);
                break;

            case Query::OPERATOR_STARTS_WITH:
                $expr = $expr->startsWith($fieldName, $this->stream->current->value);
                break;

            default:
                throw new ExpressionSyntaxError(
                    sprintf('Unsupported operator "%s"', $operatorToken->value),
                    $operatorToken->cursor
                );
        }

        $this->stream->next();

        return $expr;
    }

    /**
     * @param Token $operatorToken
     * @param ExpressionBuilder $expr
     * @param string $fieldName
     * @return Comparison
     */
    private function getComparisonForInAndExistsOperators(Token $operatorToken, ExpressionBuilder $expr, $fieldName)
    {
        switch ($operatorToken->value) {
            case Query::OPERATOR_IN:
                return $expr->in($fieldName, $this->parseArguments());

            case Query::OPERATOR_NOT_IN:
                return $expr->notIn($fieldName, $this->parseArguments());

            case Query::OPERATOR_EXISTS:
                return $expr->exists($fieldName);

            case Query::OPERATOR_NOT_EXISTS:
                return $expr->notExists($fieldName);

            default:
                return null;
        }
    }
}
