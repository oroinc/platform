<?php

namespace Oro\Bundle\SearchBundle\Expression;

use Doctrine\Common\Collections\Criteria;

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
     * @param ObjectMapper $mapper;
     * @param Query|null $query
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
                    //$this->stream->next();
                    $this->parseFromExpression();
                    break;

                case Query::KEYWORD_WHERE:
                    //$this->stream->next();
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
        if ($this->stream->current->test(Token::KEYWORD_TYPE, 'where')) {
            $this->stream->next();
            while (!$this->stream->isEOF()) {
                /** @var Token $token */
                $token = $this->stream->current;
                switch ($token->type) {
                    case Token::PUNCTUATION_TYPE:

                        break;

                    case Token::STRING_TYPE:
                        $this->parseSimpleCondition();
                        break;

                    case Token::OPERATOR_TYPE:
                        $this->parseCompositeCondition();
                        break;

                    default:
                        break;
                }
            }
        }
    }

    protected function parseSimpleCondition()
    {
        /** @var Token $token */
        $token = $this->stream->current;

        //$criteria = $this->query->getCriteria();

        $criteriaExpr = Criteria::expr();

        $this->stream->next();
        switch ($this->stream->current->value) {
            case Query::OPERATOR_CONTAINS:
                $this->stream->next();
//                $criteria->where($expr->contains($token->value, $this->stream->current->value));
                $this->stream->next();
                break;

            case Query::OPERATOR_NOT_CONTAINS:
                $this->stream->next();
//                $criteria->where($criteria->expr()->($token->value, $this->stream->current->value));
                $this->stream->next();
                break;

            case Query::OPERATOR_EQUALS:
                $this->stream->next();

//                $criteria->where($criteria->expr()->eq($token->value, $this->stream->current->value));
//                $criteria->andWhere(
//                    $criteria->expr()->andX(
//                        $criteria->expr()->eq($token->value, $this->stream->current->value),
//                        $criteria->expr()->eq('description', 'test')
//                    )
//                );

                $this->stream->next();
                break;

            case Query::OPERATOR_NOT_EQUALS:
                $this->stream->next();
//                $criteria->where($criteria->expr()->neq($token->value, $this->stream->current->value));
                $this->stream->next();
                break;

            default:
                break;
        }

        //$criteria->andWhere($expr->eq('name', 'Acuserv'))

        //$this->query->setCriteria($criteria);

        return $criteriaExpr;
    }

    protected function parseCompositeCondition()
    {
        /** @var Token $token */
        $token = $this->stream->current;

        throw new SyntaxError(
            sprintf('composite expression.'),
            $token->cursor
        );
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
                    sprintf('Wrong "where" part of the expression.'),
                    $token->cursor
                );
            }
            $this->stream->next();
        }

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
