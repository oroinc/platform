<?php

/*
 * This file is a modified copy of {@see \Symfony\Component\ExpressionLanguage\Lexer}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Component\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;
use Symfony\Component\ExpressionLanguage\TokenStream;

// @codingStandardsIgnoreStart

/**
 * Copy of {@see \Symfony\Component\ExpressionLanguage\Lexer} with the following changes:
 * 1 Adds the ability to tokenize "=" operator
 *
 * Custom lines are located between comments [CUSTOM LINES]...[/CUSTOM LINES]
 *
 * Version of the "symfony/expression-language" component used as a source: 5.3.7
 * {@see https://github.com/symfony/expression-language/blob/v5.3.7/Lexer.php}
 */
class Lexer
{
    /**
     * Tokenizes an expression.
     * Differs from {@see \Symfony\Component\ExpressionLanguage\Lexer} by:
     * 1 Added ability to tokenize "=" operator
     *
     * @return TokenStream A token stream instance
     *
     * @throws SyntaxError
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function tokenize(string $expression)
    {
        $expression = str_replace(["\r", "\n", "\t", "\v", "\f"], ' ', $expression);
        $cursor = 0;
        $tokens = [];
        $brackets = [];
        $end = \strlen($expression);

        while ($cursor < $end) {
            if (' ' == $expression[$cursor]) {
                ++$cursor;

                continue;
            }

            if (preg_match('/[0-9]+(?:\.[0-9]+)?([Ee][\+\-][0-9]+)?/A', $expression, $match, 0, $cursor)) {
                // numbers
                $number = (float) $match[0];  // floats
                if (preg_match('/^[0-9]+$/', $match[0]) && $number <= \PHP_INT_MAX) {
                    $number = (int) $match[0]; // integers lower than the maximum
                }
                $tokens[] = new Token(Token::NUMBER_TYPE, $number, $cursor + 1);
                $cursor += \strlen($match[0]);
            } elseif (false !== strpos('([{', $expression[$cursor])) {
                // opening bracket
                $brackets[] = [$expression[$cursor], $cursor];

                $tokens[] = new Token(Token::PUNCTUATION_TYPE, $expression[$cursor], $cursor + 1);
                ++$cursor;
            } elseif (false !== strpos(')]}', $expression[$cursor])) {
                // closing bracket
                if (empty($brackets)) {
                    throw new SyntaxError(sprintf('Unexpected "%s".', $expression[$cursor]), $cursor, $expression);
                }

                [$expect, $cur] = array_pop($brackets);
                if ($expression[$cursor] != strtr($expect, '([{', ')]}')) {
                    throw new SyntaxError(sprintf('Unclosed "%s".', $expect), $cur, $expression);
                }

                $tokens[] = new Token(Token::PUNCTUATION_TYPE, $expression[$cursor], $cursor + 1);
                ++$cursor;
            } elseif (preg_match('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As', $expression, $match, 0, $cursor)) {
                // strings
                $tokens[] = new Token(Token::STRING_TYPE, stripcslashes(substr($match[0], 1, -1)), $cursor + 1);
                $cursor += \strlen($match[0]);
            } elseif (preg_match('/(?<=^|[\s(])not in(?=[\s(])|\!\=\=|(?<=^|[\s(])not(?=[\s(])|(?<=^|[\s(])and(?=[\s(])|\=\=\=|\>\=|(?<=^|[\s(])or(?=[\s(])|\<\=|\*\*|\.\.|(?<=^|[\s(])in(?=[\s(])|&&|\|\||(?<=^|[\s(])matches|\=\=|\!\=|\*|~|%|\/|\>|\||\!|\^|&|\+|\<|\-/A', $expression, $match, 0, $cursor)) {
                // operators
                $tokens[] = new Token(Token::OPERATOR_TYPE, $match[0], $cursor + 1);
                $cursor += \strlen($match[0]);
            // [CUSTOM LINES]
            } elseif (preg_match('/\=/A', $expression, $match, 0, $cursor)) {
                // "=" operator
                $tokens[] = new Token(Token::OPERATOR_TYPE, $match[0], $cursor + 1);
                $cursor += \strlen($match[0]);
            // [/CUSTOM LINES]
            } elseif (false !== strpos('.,?:', $expression[$cursor])) {
                // punctuation
                $tokens[] = new Token(Token::PUNCTUATION_TYPE, $expression[$cursor], $cursor + 1);
                ++$cursor;
            } elseif (preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A', $expression, $match, 0, $cursor)) {
                // names
                $tokens[] = new Token(Token::NAME_TYPE, $match[0], $cursor + 1);
                $cursor += \strlen($match[0]);
            } else {
                // unlexable
                throw new SyntaxError(sprintf('Unexpected character "%s".', $expression[$cursor]), $cursor, $expression);
            }
        }

        $tokens[] = new Token(Token::EOF_TYPE, null, $cursor + 1);

        if (!empty($brackets)) {
            [$expect, $cur] = array_pop($brackets);
            throw new SyntaxError(sprintf('Unclosed "%s".', $expect), $cur, $expression);
        }

        return new TokenStream($tokens, $expression);
    }
}
// @codingStandardsIgnoreEnd
