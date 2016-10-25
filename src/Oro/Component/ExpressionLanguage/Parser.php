<?php

namespace Oro\Component\ExpressionLanguage;

use Oro\Component\ExpressionLanguage\Node as CustomNode;
use Symfony\Component\ExpressionLanguage\Node;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;
use Symfony\Component\ExpressionLanguage\TokenStream;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * Copy of \Symfony\Component\ExpressionLanguage\Parser with
 * denied methods calls, special "all" and "any" methods for collections with expression as argument
 */
class Parser
{
    const OPERATOR_LEFT = 1;
    const OPERATOR_RIGHT = 2;

    /**
     * @var TokenStream
     */
    private $stream;

    /**
     * @var array
     */
    private $unaryOperators;

    /**
     * @var array
     */
    private $binaryOperators;

    /**
     * @var array
     */
    private $functions;

    /**
     * @var array
     */
    private $names;

    /**
     * @var array
     */
    private $methods;

    /**
     * @param array $functions
     */
    public function __construct(array $functions)
    {
        $this->functions = $functions;

        $this->unaryOperators = [
            'not' => ['precedence' => 50],
            '!' => ['precedence' => 50],
            '-' => ['precedence' => 500],
            '+' => ['precedence' => 500],
        ];
        $this->binaryOperators = [
            'or' => ['precedence' => 10, 'associativity' => self::OPERATOR_LEFT],
            '||' => ['precedence' => 10, 'associativity' => self::OPERATOR_LEFT],
            'and' => ['precedence' => 15, 'associativity' => self::OPERATOR_LEFT],
            '&&' => ['precedence' => 15, 'associativity' => self::OPERATOR_LEFT],
            '|' => ['precedence' => 16, 'associativity' => self::OPERATOR_LEFT],
            '^' => ['precedence' => 17, 'associativity' => self::OPERATOR_LEFT],
            '&' => ['precedence' => 18, 'associativity' => self::OPERATOR_LEFT],
            '=' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            '!=' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            '<' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            '>' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            '>=' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            '<=' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            'not in' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            'in' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            'matches' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
            '..' => ['precedence' => 25, 'associativity' => self::OPERATOR_LEFT],
            '+' => ['precedence' => 30, 'associativity' => self::OPERATOR_LEFT],
            '-' => ['precedence' => 30, 'associativity' => self::OPERATOR_LEFT],
            '~' => ['precedence' => 40, 'associativity' => self::OPERATOR_LEFT],
            '*' => ['precedence' => 60, 'associativity' => self::OPERATOR_LEFT],
            '/' => ['precedence' => 60, 'associativity' => self::OPERATOR_LEFT],
            '%' => ['precedence' => 60, 'associativity' => self::OPERATOR_LEFT],
            '**' => ['precedence' => 200, 'associativity' => self::OPERATOR_RIGHT],
        ];
        $this->methods = [
            'all' => CustomNode\GetAttrNode::ALL_CALL,
            'any' => CustomNode\GetAttrNode::ANY_CALL,
        ];
    }

    /**
     * Converts a token stream to a node tree.
     *
     * The valid names is an array where the values
     * are the names that the user can use in an expression.
     *
     * If the variable name in the compiled PHP code must be
     * different, define it as the key.
     *
     * For instance, ['this' => 'container'] means that the
     * variable 'container' can be used in the expression
     * but the compiled code will use 'this'.
     *
     * @param TokenStream $stream A token stream instance
     * @param array $names An array of valid names
     *
     * @return Node\Node A node tree
     *
     * @throws SyntaxError
     */
    public function parse(TokenStream $stream, $names = [])
    {
        $this->stream = $stream;
        $this->names = $names;

        $node = $this->parseExpression();
        if (!$stream->isEOF()) {
            throw new SyntaxError(
                sprintf('Unexpected token "%s" of value "%s"', $stream->current->type, $stream->current->value),
                $stream->current->cursor
            );
        }

        return $node;
    }

    /**
     * @param int $precedence
     * @return CustomNode\BinaryNode|CustomNode\GetAttrNode|Node\ConditionalNode|Node\ConstantNode
     */
    public function parseExpression($precedence = 0)
    {
        $expr = $this->getPrimary();
        $token = $this->stream->current;
        while ($token->test(Token::OPERATOR_TYPE)
            && isset($this->binaryOperators[$token->value])
            && $this->binaryOperators[$token->value]['precedence'] >= $precedence
        ) {
            $op = $this->binaryOperators[$token->value];
            $this->stream->next();

            $expr1 = $this->parseExpression(
                self::OPERATOR_LEFT === $op['associativity'] ? $op['precedence'] + 1 : $op['precedence']
            );
            $expr = new CustomNode\BinaryNode($token->value, $expr, $expr1);

            $token = $this->stream->current;
        }

        if (0 === $precedence) {
            return $this->parseConditionalExpression($expr);
        }

        return $expr;
    }

    /**
     * @return CustomNode\GetAttrNode|Node\ConstantNode
     */
    protected function getPrimary()
    {
        $token = $this->stream->current;

        if ($token->test(Token::OPERATOR_TYPE) && isset($this->unaryOperators[$token->value])) {
            $operator = $this->unaryOperators[$token->value];
            $this->stream->next();
            $expr = $this->parseExpression($operator['precedence']);

            return $this->parsePostfixExpression(new Node\UnaryNode($token->value, $expr));
        }

        if ($token->test(Token::PUNCTUATION_TYPE, '(')) {
            $this->stream->next();
            $expr = $this->parseExpression();
            $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'An opened parenthesis is not properly closed');

            return $this->parsePostfixExpression($expr);
        }

        return $this->parsePrimaryExpression();
    }

    /**
     * @param $expr
     * @return Node\ConditionalNode
     */
    protected function parseConditionalExpression($expr)
    {
        while ($this->stream->current->test(Token::PUNCTUATION_TYPE, '?')) {
            $this->stream->next();
            if (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ':')) {
                $expr2 = $this->parseExpression();
                if ($this->stream->current->test(Token::PUNCTUATION_TYPE, ':')) {
                    $this->stream->next();
                    $expr3 = $this->parseExpression();
                } else {
                    $expr3 = new Node\ConstantNode(null);
                }
            } else {
                $this->stream->next();
                $expr2 = $expr;
                $expr3 = $this->parseExpression();
            }

            $expr = new Node\ConditionalNode($expr, $expr2, $expr3);
        }

        return $expr;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return CustomNode\GetAttrNode|Node\ConstantNode
     */
    public function parsePrimaryExpression()
    {
        $token = $this->stream->current;
        switch ($token->type) {
            case Token::NAME_TYPE:
                $this->stream->next();
                switch ($token->value) {
                    case 'true':
                    case 'TRUE':
                        return new Node\ConstantNode(true);

                    case 'false':
                    case 'FALSE':
                        return new Node\ConstantNode(false);

                    case 'null':
                    case 'NULL':
                        return new Node\ConstantNode(null);

                    default:
                        if ('(' === $this->stream->current->value) {
                            if (false === isset($this->functions[$token->value])) {
                                throw new SyntaxError(
                                    sprintf('The function "%s" does not exist', $token->value),
                                    $token->cursor
                                );
                            }

                            $node = new Node\FunctionNode($token->value, $this->parseArguments());
                        } else {
                            $name = array_search($token->value, $this->names, true);
                            if (is_int($name) || $name === false) {
                                $name = $token->value;
                            }
                            $node = new Node\NameNode($name);
                        }
                }
                break;

            case Token::NUMBER_TYPE:
            case Token::STRING_TYPE:
                $this->stream->next();

                return new Node\ConstantNode($token->value);

            default:
                if ($token->test(Token::PUNCTUATION_TYPE, '[')) {
                    $node = $this->parseArrayExpression();
                } elseif ($token->test(Token::PUNCTUATION_TYPE, '{')) {
                    $node = $this->parseHashExpression();
                } else {
                    throw new SyntaxError(
                        sprintf('Unexpected token "%s" of value "%s"', $token->type, $token->value),
                        $token->cursor
                    );
                }
        }

        return $this->parsePostfixExpression($node);
    }

    /**
     * @return Node\ArrayNode
     */
    public function parseArrayExpression()
    {
        $this->stream->expect(Token::PUNCTUATION_TYPE, '[', 'An array element was expected');

        $node = new Node\ArrayNode();
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

    /**
     * @return Node\ArrayNode
     */
    public function parseHashExpression()
    {
        $this->stream->expect(Token::PUNCTUATION_TYPE, '{', 'A hash element was expected');

        $node = new Node\ArrayNode();
        $first = true;
        while (!$this->stream->current->test(Token::PUNCTUATION_TYPE, '}')) {
            if (!$first) {
                $this->stream->expect(Token::PUNCTUATION_TYPE, ',', 'A hash value must be followed by a comma');

                // trailing ,?
                if ($this->stream->current->test(Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }
            $first = false;

            // a hash key can be:
            //
            //  * a number -- 12
            //  * a string -- 'a'
            //  * a name, which is equivalent to a string -- a
            //  * an expression, which must be enclosed in parentheses -- (1 + 2)
            if ($this->stream->current->test(Token::STRING_TYPE)
                || $this->stream->current->test(Token::NAME_TYPE)
                || $this->stream->current->test(Token::NUMBER_TYPE)
            ) {
                $key = new Node\ConstantNode($this->stream->current->value);
                $this->stream->next();
            } elseif ($this->stream->current->test(Token::PUNCTUATION_TYPE, '(')) {
                $key = $this->parseExpression();
            } else {
                $current = $this->stream->current;

                throw new SyntaxError(sprintf(
                    'A hash key must be a quoted string,'
                    .' a number, a name, or an expression enclosed in parentheses (unexpected token "%s" of value "%s"',
                    $current->type,
                    $current->value
                ), $current->cursor);
            }

            $this->stream->expect(Token::PUNCTUATION_TYPE, ':', 'A hash key must be followed by a colon (:)');
            $value = $this->parseExpression();

            $node->addElement($value, $key);
        }
        $this->stream->expect(Token::PUNCTUATION_TYPE, '}', 'An opened hash is not properly closed');

        return $node;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param $node
     * @return CustomNode\GetAttrNode
     */
    public function parsePostfixExpression($node)
    {
        $token = $this->stream->current;
        while ($token->type == Token::PUNCTUATION_TYPE) {
            if ('.' === $token->value) {
                $this->stream->next();
                $token = $this->stream->current;
                $this->stream->next();

                if ($token->type !== Token::NAME_TYPE
                    &&
                    // Operators like "not" and "matches" are valid method or property names,
                    //
                    // In other words, besides NAME_TYPE, OPERATOR_TYPE could also be parsed as a property or method.
                    // This is because operators are processed by the lexer prior to names.
                    // So "not" in "foo.not()" or "matches" in "foo.matches" will be recognized as an operator first.
                    // But in fact, "not" and "matches" in such expressions shall be parsed as method or property names.
                    //
                    // And this ONLY works if the operator consists of valid characters for a property or method name.
                    //
                    // Other types, such as STRING_TYPE and NUMBER_TYPE, can't be parsed as property nor method names.
                    //
                    // As a result,
                    // if $token is NOT an operator OR $token->value is NOT a valid property or method name,
                    // an exception shall be thrown.
                    (
                        $token->type !== Token::OPERATOR_TYPE
                        || !preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A', $token->value)
                    )
                ) {
                    throw new SyntaxError('Expected name', $token->cursor);
                }

                $arg = new Node\ConstantNode($token->value);

                $arguments = new Node\ArgumentsNode();
                if ($this->stream->current->test(Token::PUNCTUATION_TYPE, '(')) {
                    $methodName = strtolower($token->value);
                    if (!array_key_exists($methodName, $this->methods)) {
                        throw new SyntaxError(sprintf(
                            'Unsupported method: %s, supported methods are %s',
                            $token->value,
                            implode(', ', $this->methods)
                        ), $token->cursor);
                    }
                    $argumentNodes = $this->parseArguments()->nodes;
                    if (count($argumentNodes) !== 1) {
                        throw new SyntaxError('Method should have exactly one argument', $token->cursor);
                    }
                    $arguments = reset($argumentNodes);
                    $type = $this->methods[$methodName];
                } else {
                    $type = CustomNode\GetAttrNode::PROPERTY_CALL;
                }

                $node = new CustomNode\GetAttrNode($node, $arg, $arguments, $type);
            } elseif ('[' === $token->value) {
                if ($node instanceof CustomNode\GetAttrNode
                    && in_array($node->attributes['type'], $this->methods, true)
                ) {
                    throw new SyntaxError('Array calls on a method call is not allowed', $token->cursor);
                }

                $this->stream->next();
                $arg = $this->parseExpression();
                $this->stream->expect(Token::PUNCTUATION_TYPE, ']');

                $node = new CustomNode\GetAttrNode(
                    $node,
                    $arg,
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ARRAY_CALL
                );
            } else {
                break;
            }

            $token = $this->stream->current;
        }

        return $node;
    }

    /**
     * Parses arguments.
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

            $args[] = $this->parseExpression();
        }
        $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return new Node\Node($args);
    }
}
