<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Parser
{
    /** @var TokenStream */
    protected $stream;

    /** @var Query */
    protected $query;

    /** @var array */
    protected $keywords = [
        Query::KEYWORD_SELECT,
        Query::KEYWORD_FROM,
        Query::KEYWORD_WHERE,
        Query::KEYWORD_AGGREGATE,

        Query::KEYWORD_AND,
        Query::KEYWORD_OR,

        Query::KEYWORD_OFFSET,
        Query::KEYWORD_MAX_RESULTS,
        Query::KEYWORD_ORDER_BY,

        Query::KEYWORD_AS
    ];

    /** @var array */
    protected $operators = [
        Query::OPERATOR_GREATER_THAN,
        Query::OPERATOR_GREATER_THAN_EQUALS,
        Query::OPERATOR_LESS_THAN,
        Query::OPERATOR_LESS_THAN_EQUALS,
        Query::OPERATOR_EQUALS,
        Query::OPERATOR_NOT_EQUALS,
        Query::OPERATOR_IN,
        Query::OPERATOR_NOT_IN,
        Query::OPERATOR_CONTAINS,
        Query::OPERATOR_NOT_CONTAINS,
        Query::OPERATOR_STARTS_WITH,
        Query::OPERATOR_LIKE,
        Query::OPERATOR_NOT_LIKE,
    ];

    /** @var array */
    protected $types = [
        Query::TYPE_TEXT,
        Query::TYPE_DATETIME,
        Query::TYPE_DECIMAL,
        Query::TYPE_INTEGER,
    ];

    /** @var array */
    protected $typeOperators = [
        Query::TYPE_TEXT     => [
            Query::OPERATOR_CONTAINS,
            Query::OPERATOR_NOT_CONTAINS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_STARTS_WITH,
            Query::OPERATOR_EXISTS,
            Query::OPERATOR_NOT_EXISTS,
            Query::OPERATOR_LIKE,
            Query::OPERATOR_NOT_LIKE,
        ],
        Query::TYPE_INTEGER  => [
            Query::OPERATOR_GREATER_THAN,
            Query::OPERATOR_GREATER_THAN_EQUALS,
            Query::OPERATOR_LESS_THAN,
            Query::OPERATOR_LESS_THAN_EQUALS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_EXISTS,
            Query::OPERATOR_NOT_EXISTS,
        ],
        Query::TYPE_DECIMAL  => [
            Query::OPERATOR_GREATER_THAN,
            Query::OPERATOR_GREATER_THAN_EQUALS,
            Query::OPERATOR_LESS_THAN,
            Query::OPERATOR_LESS_THAN_EQUALS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_EXISTS,
            Query::OPERATOR_NOT_EXISTS,
        ],
        Query::TYPE_DATETIME => [
            Query::OPERATOR_GREATER_THAN,
            Query::OPERATOR_GREATER_THAN_EQUALS,
            Query::OPERATOR_LESS_THAN,
            Query::OPERATOR_LESS_THAN_EQUALS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_EXISTS,
            Query::OPERATOR_NOT_EXISTS,
        ]
    ];

    /** @var array */
    protected $aggregatingFunctions = [
        Query::AGGREGATE_FUNCTION_COUNT,
        Query::AGGREGATE_FUNCTION_SUM,
        Query::AGGREGATE_FUNCTION_MAX,
        Query::AGGREGATE_FUNCTION_MIN,
        Query::AGGREGATE_FUNCTION_AVG,
    ];

    /** @var array */
    protected $orderDirections = [
        Query::ORDER_ASC,
        Query::ORDER_DESC,
    ];

    /**
     * @param Query|null $query
     */
    public function __construct($query = null)
    {
        if (null === $query) {
            $this->query = new Query();
            $this->query->from(['*']);
        } else {
            $this->query = $query;
        }
    }

    /**
     * @param TokenStream $stream
     *
     * @throws ExpressionSyntaxError
     * @return Query
     */
    public function parse(TokenStream $stream)
    {
        /** @var TokenStream stream */
        $this->stream = $stream;

        while (!$this->stream->isEOF()
            && $this->stream->current->test(Token::KEYWORD_TYPE)
            && in_array($this->stream->current->value, $this->keywords)
        ) {
            $this->parseKeywords();
        }

        if (!$stream->isEOF()) {
            throw new ExpressionSyntaxError(
                sprintf('Unexpected token "%s", value "%s"', $stream->current->type, $stream->current->value),
                $stream->current->cursor
            );
        }

        return $this->query;
    }

    protected function parseKeywords()
    {
        switch ($this->stream->current->value) {
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

                    $fieldDeclaration .= ' '.Query::KEYWORD_AS.' '.$aliasName;

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
                    sprintf('Wrong "select" statement of the expression.'),
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
                    sprintf('Wrong "from" statement of the expression.'),
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
        while (!$this->stream->isEOF() && !$exit) {
            /** @var Token $token */
            $token = $this->stream->current;
            $exit = $this->parseToken($token);
        }
    }

    /**
     * Parse aggregate expression from string query
     */
    protected function parseAggregateExpression()
    {
        /** @var Criteria $criteria */
        $criteria = $this->query->getCriteria();

        // skip AGGREGATE keyword
        $this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_AGGREGATE);

        // parse field name
        $fieldTypeToken = $this->stream->expect(Token::STRING_TYPE, $this->types, null, false);
        $field = $criteria->implodeFieldTypeName(
            $fieldTypeToken ? $fieldTypeToken->value : Query::TYPE_TEXT,
            $this->stream->expect(Token::STRING_TYPE, null, 'Aggregating field is expected')->value
        );

        // parse function
        $functionToken = $this->stream->expect(
            Token::STRING_TYPE,
            $this->aggregatingFunctions,
            'Aggregating function expected'
        );
        $function = $functionToken->value;

        // skip optional AS keyword
        if ($this->stream->current->test(Token::KEYWORD_TYPE, Query::KEYWORD_AS)) {
            $this->stream->next();
        }

        // parse aggregating name
        $nameToken = $this->stream->expect(Token::STRING_TYPE, null, 'Aggregating name is expected');
        $name = $nameToken->value;

        $this->query->addAggregate($name, $field, $function);
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

        $orderFieldType = $this->stream->expect(Token::STRING_TYPE, $this->types, null, false);
        $orderFieldName = $criteria->implodeFieldTypeName(
            $orderFieldType ? $orderFieldType->value : Query::TYPE_TEXT,
            $this->stream->expect(Token::STRING_TYPE, null, 'Ordering field name is expected')->value
        );

        $orderDirection = Criteria::ASC;
        if (!$this->stream->isEOF() && $this->stream->current->test(Token::STRING_TYPE)) {
            $orderDirectionToken = $this->stream->expect(Token::STRING_TYPE, $this->orderDirections, null, false);
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

        /**
         * We expecting that both field type and field name will be specified
         */
        $fieldTypeToken = $this->stream->expect(Token::STRING_TYPE, null, null, false);
        $fieldNameToken = $this->stream->expect(Token::STRING_TYPE, null, null, false);

        $fieldType = Query::TYPE_TEXT;
        if (!$fieldNameToken) {
            /**
             * If field type is not specified we got field name in first expect ($fieldTypeToken)
             */
            $fieldName = Criteria::implodeFieldTypeName($fieldType, $fieldTypeToken->value);
        } else {
            $fieldType = $fieldTypeToken->value;
            $fieldName = Criteria::implodeFieldTypeName($fieldType, $fieldNameToken->value);
        }

        if (!isset($this->typeOperators[$fieldType])) {
            throw new ExpressionSyntaxError(
                sprintf('Unknown field type "%s"', $fieldType),
                $this->stream->current->cursor
            );
        }

        /** @var Token $operatorToken */
        $operatorToken = $this->stream->expect(
            Token::OPERATOR_TYPE,
            $this->typeOperators[$fieldType],
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
                            sprintf(
                                'Syntax error. Composite operators of different types are not allowed on single level.'
                            ),
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
                sprintf('Syntax error in composite expression.'),
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

                $fieldDeclaration .= ' '.Query::KEYWORD_AS.' '.$aliasName;
                $this->stream->next();
            }

            $args[] = $fieldDeclaration;
        }

        $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return $args;
    }

    /**
     * @param Token $token
     * @return bool
     */
    private function parseToken(Token $token)
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
                list($type, $expr) = $this->parseSimpleCondition();
                $this->query->getCriteria()->{$type}($expr);
                break;
            case Token::OPERATOR_TYPE && in_array($token->value, [Query::KEYWORD_AND, Query::KEYWORD_OR]):
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

        return $exit;
    }

    /**
     * @param Token $operatorToken
     * @param ExpressionBuilder $expr
     * @param string $fieldName
     * @return Comparison
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
