<?php

namespace Oro\Bundle\SearchBundle\Expression;

use Doctrine\Common\Collections\Criteria;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Exception\SyntaxError;
use Oro\Bundle\SearchBundle\Query\Query;

class Parser
{
    const OPERATOR_LEFT  = 1;
    const OPERATOR_RIGHT = 2;

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
    protected $mappingConfig;

    /**
     * @param ObjectMapper $mapper ;
     * @param Query|null   $query
     */
    public function __construct(ObjectMapper $mapper, $query = null)
    {
        $this->mappingConfig = $mapper->getMappingConfig();

        if (null === $query) {
            $this->query = new Query(Query::SELECT);
        } else {
            $this->query = $query;
        }

        $this->keywords = [
            Query::KEYWORD_FROM        => ['precedence' => 50],
            Query::KEYWORD_WHERE       => ['precedence' => 50],

            Query::KEYWORD_AND         => ['precedence' => 50],
            Query::KEYWORD_OR          => ['precedence' => 50],

            Query::KEYWORD_ORDER_BY    => ['precedence' => 500],
            Query::KEYWORD_OFFSET      => ['precedence' => 500],
            Query::KEYWORD_MAX_RESULTS => ['precedence' => 500],
        ];

        $this->reservedWords = [
            Indexer::TEXT_ALL_DATA_FIELD
        ];

//        $this->unaryOperators  = [
//            'not' => ['precedence' => 50],
//            '!'   => ['precedence' => 50],
//            '-'   => ['precedence' => 500],
//            '+'   => ['precedence' => 500],
//        ];

        $this->operators = [
            '='   => ['precedence' => 20, 'associativity' => Parser::OPERATOR_LEFT],
            '!='  => ['precedence' => 20, 'associativity' => Parser::OPERATOR_LEFT],
            '<'   => ['precedence' => 20, 'associativity' => Parser::OPERATOR_LEFT],
            '>'   => ['precedence' => 20, 'associativity' => Parser::OPERATOR_LEFT],
            '>='  => ['precedence' => 20, 'associativity' => Parser::OPERATOR_LEFT],
            '<='  => ['precedence' => 20, 'associativity' => Parser::OPERATOR_LEFT],
            'in'  => ['precedence' => 20, 'associativity' => Parser::OPERATOR_LEFT],
            '!in' => ['precedence' => 20, 'associativity' => Parser::OPERATOR_LEFT],
            '~'   => ['precedence' => 40, 'associativity' => Parser::OPERATOR_LEFT],
            '!~'  => ['precedence' => 40, 'associativity' => Parser::OPERATOR_LEFT]
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
                Query::OPERATOR_IN,
                Query::OPERATOR_NOT_IN,
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
            && isset($this->keywords[$this->stream->current->value])
        ) {
            /** @var Token $token */
            $token = $this->stream->current;

            switch ($token->value) {
                case Query::KEYWORD_FROM:
                    $this->parseFromExpression();
                    break;

                case Query::KEYWORD_WHERE:
                    $this->parseWhereExpression();
                    break;

                case Query::KEYWORD_OFFSET:
                case Query::KEYWORD_ORDER_BY:
                case Query::KEYWORD_MAX_RESULTS:
                    break;

                default:
                    throw new SyntaxError(
                        sprintf('Unexpected token "%s" of value "%s"', $stream->current->type, $stream->current->value),
                        $stream->current->cursor
                    );
                    break;
            }
        }

        if (!$stream->isEOF()) {
            throw new SyntaxError(
                sprintf('Unexpected token "%s" of value "%s"', $stream->current->type, $stream->current->value),
                $stream->current->cursor
            );
        }

        return $this->query;
    }

    protected function parseWhereExpression()
    {
        $exit = false;
        $this->stream->next();
        while (!$this->stream->isEOF() || $exit) {
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

                default:
                    throw new SyntaxError(
                        sprintf('Unexpected token "%s" in where statement', $this->stream->current->type),
                        $this->stream->current->cursor
                    );
            }
        }
    }

    /**
     * @param null|string $whereType can be 'and' | 'or'
     *                               @ return \Doctrine\Common\Collections\ExpressionBuilder
     * @return array
     *                               key 0 -> type
     *                               key 1 -> expression
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
            /**
             * TODO : field type parsing
             */
            //$fieldName = $fieldNameToken->value;
            //$this->stream->next();

        } elseif ($this->stream->current->test(Token::OPERATOR_TYPE)) {
            $fieldType = Query::TYPE_TEXT;
            //} elseif ($this->stream->current->test(Token::PUNCTUATION_TYPE)) {
        } else {
            throw new SyntaxError(
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
            throw new SyntaxError(
                sprintf('Not allowed operator "%s"', $operatorToken->value),
                $operatorToken->cursor
            );
        }

        $this->stream->next();
        switch ($operatorToken->value) {
            case Query::OPERATOR_CONTAINS:
                //$criteria->where($expr->contains($fieldName, $this->stream->current->value));
                //$criteria->{$whereType}(
                $expr = $expr->contains($fieldName, $this->stream->current->value);
                //);
                break;

            case Query::OPERATOR_NOT_CONTAINS:
                //$criteria->andwhere($expr()->  ($token->value, $this->stream->current->value));
                break;

            case Query::OPERATOR_EQUALS:
                //$criteria->{$whereType}($expr->eq($fieldName, $this->stream->current->value));
                break;

            case Query::OPERATOR_NOT_EQUALS:
                //$criteria->{$whereType}($expr->neq($fieldName, $this->stream->current->value));
                break;

            default:
                break;
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
            $expressions[$typeX][] = $expression;
        }

        $expr = Criteria::expr();
        if ($expressions) {
            foreach ($expressions as $typeX => $subExpressions) {
                $expr = call_user_func_array([$expr, str_replace('Where', 'X', $typeX)], $subExpressions);
            }
        } else {
            throw new SyntaxError(sprintf('Syntax error in composite expression.'), $this->stream->current->cursor);
        }

        $this->stream->next();
        return $expr;
    }

    /**
     * @return null
     */
    protected function parseFromExpression()
    {
        $fromParts = [];
        $this->stream->next();

        /** @var Token $token */
        $token = $this->stream->current;

        // if get string token after "from" - pass it directly into Query
        if ($token->test(Token::STRING_TYPE)) {
            $this->stream->next();
            $this->query->from($token->value);

            return null;
        }

        // if get operator - only '*' is supported in from statement
        if ($token->test(Token::OPERATOR_TYPE)) {
            if ($token->test(Token::OPERATOR_TYPE, '*')) {
                $this->query->from(['*']);
            } else {
                throw new SyntaxError(
                    sprintf('Unexpected operator in from statement of the expression.'),
                    $token->cursor
                );
            }
        }

        // if punctuation '(' || ',' || ')' should collect all until closing bracket skipping any inner punctuation
        if ($token->test(Token::PUNCTUATION_TYPE, '(')) {
            while (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ')')) {
                if ($this->stream->current->test(Token::STRING_TYPE)) {
                    $fromParts[] = $this->stream->current->value;
                }
                $this->stream->next();
            }
            if (!empty($fromParts)) {
                $this->query->from($fromParts);
            } else {
                throw new SyntaxError(
                    sprintf('Wrong "from" statement of the expression.'),
                    $token->cursor
                );
            }
        }
        $this->stream->next();

        return null;
    }

    public function parseArrayExpression()
    {
        $this->stream->expect(Token::PUNCTUATION_TYPE, '[', 'An array element was expected');

        $node  = new ArrayNode();
        $first = true;
        while (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ']')) {
            if (!$first) {
                $this->stream->expect(Token::PUNCTUATION_TYPE, ',', 'An array element must be followed by a comma');

                // trailing ,?
                if ($this->stream->current->test(Token::PUNCTUATION_TYPE, ']')) {
                    break;
                }
            }
            $first = false;

            $node->addElement($this->parseExpression());
        }
        $this->stream->expect(Token::PUNCTUATION_TYPE, ']', 'An opened array is not properly closed');

        return $node;
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

            $args[] = $this->parseSimpleCondition();
        }
        $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        //return new Node($args);
    }
}
