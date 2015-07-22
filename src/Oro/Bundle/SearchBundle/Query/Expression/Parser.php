<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Query;

class Parser
{
    /** @var TokenStream */
    protected $stream;

    /** @var Query */
    protected $query;

    /** @var array */
    protected $keywords;

    /** @var array */
    protected $operators;

    /** @var array */
    protected $types;

    /** @var array */
    protected $typOperators;

    /** @var array */
    protected $orderDirections;

    /**
     * @param Query|null $query
     */
    public function __construct($query = null)
    {
        if (null === $query) {
            $this->query = new Query(Query::SELECT);
            $this->query->from(['*']);
        } else {
            $this->query = $query;
        }

        $this->keywords = [
            Query::KEYWORD_FROM,
            Query::KEYWORD_WHERE,

            Query::KEYWORD_AND,
            Query::KEYWORD_OR,

            Query::KEYWORD_OFFSET,
            Query::KEYWORD_MAX_RESULTS,
            Query::KEYWORD_ORDER_BY
        ];

        $this->operators = [
            Query::OPERATOR_GREATER_THAN,
            Query::OPERATOR_GREATER_THAN_EQUALS,
            Query::OPERATOR_LESS_THAN,
            Query::OPERATOR_LESS_THAN_EQUALS,
            Query::OPERATOR_EQUALS,
            Query::OPERATOR_NOT_EQUALS,
            Query::OPERATOR_IN,
            Query::OPERATOR_NOT_IN,
            Query::OPERATOR_CONTAINS,
            Query::OPERATOR_NOT_CONTAINS
        ];

        $this->types = [
            Query::TYPE_TEXT,
            Query::TYPE_DATETIME,
            Query::TYPE_DECIMAL,
            Query::TYPE_INTEGER,
        ];

        $this->typeOperators = [
            Query::TYPE_TEXT     => [
                Query::OPERATOR_CONTAINS,
                Query::OPERATOR_NOT_CONTAINS,
                Query::OPERATOR_EQUALS,
                Query::OPERATOR_NOT_EQUALS,
                //Query::OPERATOR_IN,
                //Query::OPERATOR_NOT_IN,
            ],
            QUERY::TYPE_INTEGER  => [
                Query::OPERATOR_GREATER_THAN,
                Query::OPERATOR_GREATER_THAN_EQUALS,
                Query::OPERATOR_LESS_THAN,
                Query::OPERATOR_LESS_THAN_EQUALS,
                Query::OPERATOR_EQUALS,
                Query::OPERATOR_NOT_EQUALS,
                Query::OPERATOR_IN,
                Query::OPERATOR_NOT_IN,
            ],
            QUERY::TYPE_DECIMAL  => [
                Query::OPERATOR_GREATER_THAN,
                Query::OPERATOR_GREATER_THAN_EQUALS,
                Query::OPERATOR_LESS_THAN,
                Query::OPERATOR_LESS_THAN_EQUALS,
                Query::OPERATOR_EQUALS,
                Query::OPERATOR_NOT_EQUALS,
                Query::OPERATOR_IN,
                Query::OPERATOR_NOT_IN,
            ],
            QUERY::TYPE_DATETIME => [
                Query::OPERATOR_GREATER_THAN,
                Query::OPERATOR_GREATER_THAN_EQUALS,
                Query::OPERATOR_LESS_THAN,
                Query::OPERATOR_LESS_THAN_EQUALS,
                Query::OPERATOR_EQUALS,
                Query::OPERATOR_NOT_EQUALS,
                Query::OPERATOR_IN,
                Query::OPERATOR_NOT_IN,
            ]
        ];

        $this->orderDirections = [
            Query::ORDER_ASC,
            Query::ORDER_DESC,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function parse(TokenStream $stream)
    {
        /** @var TokenStream stream */
        $this->stream = $stream;

        while (!$this->stream->isEOF()
            && $this->stream->current->test(Token::KEYWORD_TYPE)
            && in_array($this->stream->current->value, $this->keywords)
        ) {
            switch ($this->stream->current->value) {
                case Query::KEYWORD_FROM:
                    $this->parseFromExpression();
                    break;
                case Query::KEYWORD_WHERE:
                    $this->parseWhereExpression();
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
                        sprintf('Unexpected token "%s", value "%s"', $stream->current->type, $stream->current->value),
                        $stream->current->cursor
                    );
            }
        }

        if (!$stream->isEOF()) {
            throw new ExpressionSyntaxError(
                sprintf('Unexpected token "%s", value "%s"', $stream->current->type, $stream->current->value),
                $stream->current->cursor
            );
        }

        return $this->query;
    }

    protected function parseFromExpression()
    {
        $this->stream->next();

        /** @var Token $token */
        $token = $this->stream->current;

        // if get string token after "from" - pass it directly into Query
        if ($token->test(Token::STRING_TYPE)) {
            $this->query->from($token->value);
        } else {
            // if got operator (only '*' is supported in from statement)
            if ($token->test(Token::OPERATOR_TYPE)) {
                if ($token->test(Token::OPERATOR_TYPE, '*')) {
                    $this->query->from(['*']);
                } else {
                    throw new ExpressionSyntaxError(
                        sprintf('Unexpected operator in from statement of the expression.'),
                        $token->cursor
                    );
                }
            }

            // if opening bracket (punctuation '(') - collect all until closing bracket (skipping any inner punctuation)
            if ($token->test(Token::PUNCTUATION_TYPE, '(')) {
                $fromParts = [];
                while (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ')')) {
                    if ($this->stream->current->test(Token::STRING_TYPE)) {
                        $fromParts[] = $this->stream->current->value;
                    }
                    $this->stream->next();
                }
                if (!empty($fromParts)) {
                    $this->query->from($fromParts);
                } else {
                    throw new ExpressionSyntaxError(
                        sprintf('Wrong "from" statement of the expression.'),
                        $token->cursor
                    );
                }
            }
        }

        $this->stream->next();
    }

    protected function parseWhereExpression()
    {
        $exit = false;
        $this->stream->next();
        while (!$this->stream->isEOF() && !$exit) {
            /** @var Token $token */
            $token = $this->stream->current;
            switch ($token->type) {
                case Token::PUNCTUATION_TYPE && $token->test(Token::PUNCTUATION_TYPE, '('):
                    $this->parseCompositeCondition();
                    break;
                case Token::STRING_TYPE:
                    list ($type, $expr) = $this->parseSimpleCondition();
                    $this->query->getCriteria()->{$type}($expr);
                    break;
                case Token::OPERATOR_TYPE && in_array($token->value, [Query::KEYWORD_AND, Query::KEYWORD_OR]):
                    list ($type, $expr) = $this->parseSimpleCondition($token->value);
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
        }
    }

    protected function parseOrderByExpression()
    {
        $this->stream->expect(Token::KEYWORD_TYPE, Query::KEYWORD_ORDER_BY, 'Order By statement is expected.');

        if (count($this->query->getFrom()) > 1) {
            throw new ExpressionSyntaxError(
                sprintf(
                    'Order By expression is allowed only for searching by single entity. Token "%s", value "%s"',
                    $this->stream->current->type,
                    $this->stream->current->value
                ),
                $this->stream->current->cursor
            );
        }

        $orderFieldName = null;
        $orderFieldType = $this->stream->expect(Token::STRING_TYPE, $this->types, null, false);
        if (!$orderFieldType) {
            $orderFieldName =
                Query::TYPE_TEXT . '.' .
                $this->stream->expect(Token::STRING_TYPE, null, 'Ordering field name is expected.')->value;
        } else {
            $orderFieldName =
                $orderFieldType->value . '.' .
                $this->stream->expect(Token::STRING_TYPE, null, 'Ordering field name is expected.')->value;
        }

        $orderDirection = false;
        if (!$this->stream->isEOF() && $this->stream->current->test(Token::STRING_TYPE)) {
            $orderDirection = $this->stream->expect(Token::STRING_TYPE, $this->orderDirections, null, false);
        }

        if ($orderFieldName) {
            $this->query->getCriteria()->orderBy([$orderFieldName => $orderDirection ? : Criteria::ASC]);
        } else {
            throw new ExpressionSyntaxError('Error in order_by statement', $this->stream->current->cursor);
        }
    }

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

        if ($whereType) {
            $whereType = $whereType . 'Where';
            $this->stream->next();
        } else {
            $whereType = 'andWhere';
        }

        if ($this->stream->current->test(Token::PUNCTUATION_TYPE)) {
            return [$whereType, $this->parseCompositeCondition()];
        }

        /** @var Token $token */
        $fieldNameToken = $this->stream->current;

        $this->stream->next();

        if ($this->stream->current->test(Token::STRING_TYPE)) {
            $fieldType      = $fieldNameToken->value;
            $fieldNameToken = $this->stream->current;
            $this->stream->next();
        } elseif ($this->stream->current->test(Token::OPERATOR_TYPE)) {
            $fieldType = Query::TYPE_TEXT;
        } else {
            throw new ExpressionSyntaxError(
                sprintf(
                    'Unexpected token "%s" in comparison statement',
                    $this->stream->current->type
                ),
                $this->stream->current->cursor
            );
        }

        $fieldName = sprintf('%s.%s', $fieldType, $fieldNameToken->value);

        /** @var Token $operatorToken */
        $operatorToken = $this->stream->current;
        if (!in_array($operatorToken->value, $this->typeOperators[$fieldType])) {
            throw new ExpressionSyntaxError(
                sprintf('Not allowed operator "%s" for field type "%s"', $operatorToken->value, $fieldType),
                $operatorToken->cursor
            );
        }

        $this->stream->next();
        switch ($operatorToken->value) {
            case Query::OPERATOR_CONTAINS:
                $expr = $expr->contains($fieldName, $this->stream->current->value);
                break;
            case Query::OPERATOR_NOT_CONTAINS:
                //$criteria->andwhere($expr()->  ($token->value, $this->stream->current->value));
                throw new ExpressionSyntaxError(
                    sprintf('For now is NOT supported "%s" operator', $operatorToken->value),
                    $operatorToken->cursor
                );
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

            case Query::OPERATOR_IN:
                return [$whereType, $expr->in($fieldName, $this->parseArguments())];
            case Query::OPERATOR_NOT_IN:
                return [$whereType, $expr->notIn($fieldName, $this->parseArguments())];

            default:
                throw new ExpressionSyntaxError(
                    sprintf('Unsupported operator "%s"', $operatorToken->value),
                    $operatorToken->cursor
                );
        }

        $this->stream->next();

        return [$whereType, $expr];
    }

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
}
