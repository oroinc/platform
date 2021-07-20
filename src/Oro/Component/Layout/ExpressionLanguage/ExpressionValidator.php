<?php

namespace Oro\Component\Layout\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;
use Symfony\Component\ExpressionLanguage\TokenStream;

/**
 * Expression syntax validator
 *
 * Code based on symfony expression language parser
 * {@see https://github.com/symfony/expression-language/blob/master/Parser.php}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExpressionValidator
{
    private const OPERATOR_LEFT = 1;
    private const OPERATOR_RIGHT = 2;

    private const UNARY_OPERATORS = [
            'not' => ['precedence' => 50],
            '!' => ['precedence' => 50],
            '-' => ['precedence' => 500],
            '+' => ['precedence' => 500],
        ];

    private const BINARY_OPERATORS = [
        'or' => ['precedence' => 10, 'associativity' => self::OPERATOR_LEFT],
        '||' => ['precedence' => 10, 'associativity' => self::OPERATOR_LEFT],
        'and' => ['precedence' => 15, 'associativity' => self::OPERATOR_LEFT],
        '&&' => ['precedence' => 15, 'associativity' => self::OPERATOR_LEFT],
        '|' => ['precedence' => 16, 'associativity' => self::OPERATOR_LEFT],
        '^' => ['precedence' => 17, 'associativity' => self::OPERATOR_LEFT],
        '&' => ['precedence' => 18, 'associativity' => self::OPERATOR_LEFT],
        '==' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
        '===' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
        '!=' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
        '!==' => ['precedence' => 20, 'associativity' => self::OPERATOR_LEFT],
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

    /** @var Lexer */
    private $lexer;

    /** @var TokenStream */
    private $stream;

    /**
     * Check string for correct expression syntax
     *
     * @throws SyntaxError Wne given string with incorrect expression syntax
     */
    public function validate(string $expression): void
    {
        try {
            $this->stream = $this->getLexer()->tokenize((string)$expression);

            $this->validateExpression();

            if (!$this->stream->isEOF()) {
                throw new SyntaxError(
                    sprintf(
                        'Unexpected token "%s" of value "%s"',
                        $this->stream->current->type,
                        $this->stream->current->value
                    ),
                    $this->stream->current->cursor,
                    $this->stream->getExpression()
                );
            }
        } finally {
            $this->stream = null;
        }
    }

    private function getLexer(): Lexer
    {
        if (!$this->lexer) {
            $this->lexer = new Lexer();
        }

        return $this->lexer;
    }

    private function validateExpression(int $precedence = 0): void
    {
        $this->validatePrimary();
        $token = $this->stream->current;
        while ($token->test(Token::OPERATOR_TYPE)
            && isset(self::BINARY_OPERATORS[$token->value])
            && self::BINARY_OPERATORS[$token->value]['precedence'] >= $precedence
        ) {
            $op = self::BINARY_OPERATORS[$token->value];
            $this->stream->next();

            $this->validateExpression(
                self::OPERATOR_LEFT === $op['associativity']
                    ? $op['precedence'] + 1
                    : $op['precedence']
            );

            $token = $this->stream->current;
        }

        if (0 === $precedence) {
            $this->validateConditionalExpression();
        }
    }

    private function validatePrimary(): void
    {
        $token = $this->stream->current;

        if ($token->test(Token::OPERATOR_TYPE) && isset(self::UNARY_OPERATORS[$token->value])) {
            $operator = self::UNARY_OPERATORS[$token->value];
            $this->stream->next();
            $this->validateExpression($operator['precedence']);

            $this->validatePostfixExpression();
            return;
        }

        if ($token->test(Token::PUNCTUATION_TYPE, '(')) {
            $this->stream->next();
            $this->validateExpression();
            $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'An opened parenthesis is not properly closed');

            $this->validatePostfixExpression();
            return;
        }

        $this->validatePrimaryExpression();
    }

    private function validateConditionalExpression(): void
    {
        while ($this->stream->current->test(Token::PUNCTUATION_TYPE, '?')) {
            $this->stream->next();
            if (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ':')) {
                // Expr 2
                $this->validateExpression();
                if ($this->stream->current->test(Token::PUNCTUATION_TYPE, ':')) {
                    $this->stream->next();
                    // Expr 3
                    $this->validateExpression();
                }
            } else {
                $this->stream->next();
                // Expr 3
                $this->validateExpression();
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function validatePrimaryExpression(): void
    {
        $token = $this->stream->current;
        switch ($token->type) {
            case Token::NAME_TYPE:
                $this->stream->next();
                switch ($token->value) {
                    case 'true':
                    case 'TRUE':
                    case 'false':
                    case 'FALSE':
                    case 'null':
                    case 'NULL':
                        // Nothing do in this cases
                        return;

                    default:
                        if ('(' === $this->stream->current->value) {
                            $this->validateArguments();
                        }
                }
                break;

            case Token::NUMBER_TYPE:
            case Token::STRING_TYPE:
                $this->stream->next();

                return;

            default:
                if ($token->test(Token::PUNCTUATION_TYPE, '[')) {
                    $this->validateArrayExpression();
                } elseif ($token->test(Token::PUNCTUATION_TYPE, '{')) {
                    $this->validateHashExpression();
                } else {
                    throw new SyntaxError(
                        sprintf('Unexpected token "%s" of value "%s"', $token->type, $token->value),
                        $token->cursor,
                        $this->stream->getExpression()
                    );
                }
        }

        $this->validatePostfixExpression();
    }

    private function validateArrayExpression(): void
    {
        $this->stream->expect(Token::PUNCTUATION_TYPE, '[', 'An array element was expected');

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

            $this->validateExpression();
        }

        $this->stream->expect(Token::PUNCTUATION_TYPE, ']', 'An opened array is not properly closed');
    }

    private function validateHashExpression(): void
    {
        $this->stream->expect(Token::PUNCTUATION_TYPE, '{', 'A hash element was expected');

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
                $this->stream->next();
            } elseif ($this->stream->current->test(Token::PUNCTUATION_TYPE, '(')) {
                $this->validateExpression();
            } else {
                $current = $this->stream->current;

                throw new SyntaxError(
                    sprintf(
                        'A hash key must be a quoted string, a number, a name, or an ' .
                        'expression enclosed in parentheses (unexpected token "%s" of value "%s"',
                        $current->type,
                        $current->value
                    ),
                    $current->cursor,
                    $this->stream->getExpression()
                );
            }

            $this->stream->expect(Token::PUNCTUATION_TYPE, ':', 'A hash key must be followed by a colon (:)');
            $this->validateExpression();
        }

        $this->stream->expect(Token::PUNCTUATION_TYPE, '}', 'An opened hash is not properly closed');
    }

    private function validatePostfixExpression(): void
    {
        $token = $this->stream->current;
        while (Token::PUNCTUATION_TYPE == $token->type) {
            if ('.' === $token->value) {
                $this->stream->next();
                $token = $this->stream->current;
                $this->stream->next();

                if (Token::NAME_TYPE !== $token->type
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
                    // As a result, if $token is NOT an operator OR $token->value is NOT a valid property
                    // or method name, an exception shall be thrown.
                    && (
                        Token::OPERATOR_TYPE !== $token->type
                        || !preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A', $token->value)
                    )
                ) {
                    throw new SyntaxError('Expected name', $token->cursor, $this->stream->getExpression());
                }

                if ($this->stream->current->test(Token::PUNCTUATION_TYPE, '(')) {
                    $this->validateArguments();
                }
            } elseif ('[' === $token->value) {
                $this->stream->next();
                $this->validateExpression();
                $this->stream->expect(Token::PUNCTUATION_TYPE, ']');
            } else {
                break;
            }

            $token = $this->stream->current;
        }
    }

    private function validateArguments(): void
    {
        $this->stream->expect(
            Token::PUNCTUATION_TYPE,
            '(',
            'A list of arguments must begin with an opening parenthesis'
        );

        $first = true;
        while (!$this->stream->current->test(Token::PUNCTUATION_TYPE, ')')) {
            if (!$first) {
                $this->stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');
            }
            $first = false;

            $this->validateExpression();
        }
        $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');
    }
}
