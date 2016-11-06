<?php

namespace Oro\Component\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;
use Symfony\Component\ExpressionLanguage\TokenStream;

/**
 * Copy of \Symfony\Component\ExpressionLanguage\Lexer with "=" and without "==", "===", "!=="
 */
class Lexer
{
    /**
     * @var array
     */
    protected static $operatorsPatterns = [
        'not in(?=[\s(])',
        'not(?=[\s(])',
        'and(?=[\s(])',
        '\>\=',
        'or(?=[\s(])',
        '\<\=',
        '\*\*',
        '\.\.',
        'in(?=[\s(])',
        '&&',
        '\|\|',
        'matches',
        '\=',
        '\!\=',
        '\*',
        '~',
        '%',
        '\/',
        '\>',
        '\|',
        '\!',
        '\^',
        '&',
        '\+',
        '\<',
        '\-',
    ];

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param string $expression The expression to tokenize
     * @return TokenStream A token stream instance
     * @throws SyntaxError
     */
    public function tokenize($expression)
    {
        $expression = str_replace(["\r", "\n", "\t", "\v", "\f"], ' ', $expression);
        $cursor = 0;
        $tokens = [];
        $brackets = [];
        $end = strlen($expression);

        while ($cursor < $end) {
            if (' ' === $expression[$cursor]) {
                ++$cursor;

                continue;
            }

            if (preg_match('/[0-9]+(?:\.[0-9]+)?/A', $expression, $match, null, $cursor)) {
                // numbers
                $number = (float)$match[0];  // floats
                if (ctype_digit($match[0]) && $number <= PHP_INT_MAX) {
                    $number = (int)$match[0]; // integers lower than the maximum
                }
                $tokens[] = new Token(Token::NUMBER_TYPE, $number, $cursor + 1);
                $cursor += strlen($match[0]);
            } elseif (false !== strpos('([{', $expression[$cursor])) {
                // opening bracket
                $brackets[] = [$expression[$cursor], $cursor];

                $tokens[] = new Token(Token::PUNCTUATION_TYPE, $expression[$cursor], $cursor + 1);
                ++$cursor;
            } elseif (false !== strpos(')]}', $expression[$cursor])) {
                // closing bracket
                if (empty($brackets)) {
                    throw new SyntaxError(sprintf('Unexpected "%s"', $expression[$cursor]), $cursor);
                }

                list($expect, $cur) = array_pop($brackets);
                if ($expression[$cursor] != strtr($expect, '([{', ')]}')) {
                    throw new SyntaxError(sprintf('Unclosed "%s"', $expect), $cur);
                }

                $tokens[] = new Token(Token::PUNCTUATION_TYPE, $expression[$cursor], $cursor + 1);
                ++$cursor;
            } elseif (preg_match(
                '/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As',
                $expression,
                $match,
                null,
                $cursor
            )) {
                // strings
                $tokens[] = new Token(Token::STRING_TYPE, stripcslashes(substr($match[0], 1, -1)), $cursor + 1);
                $cursor += strlen($match[0]);
            } elseif (preg_match(sprintf(
                '/%s/A',
                implode('|', static::$operatorsPatterns)
            ), $expression, $match, null, $cursor)) {
                // operators
                $tokens[] = new Token(Token::OPERATOR_TYPE, $match[0], $cursor + 1);
                $cursor += strlen($match[0]);
            } elseif (false !== strpos('.,?:', $expression[$cursor])) {
                // punctuation
                $tokens[] = new Token(Token::PUNCTUATION_TYPE, $expression[$cursor], $cursor + 1);
                ++$cursor;
            } elseif (preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A', $expression, $match, null, $cursor)) {
                // names
                $tokens[] = new Token(Token::NAME_TYPE, $match[0], $cursor + 1);
                $cursor += strlen($match[0]);
            } else {
                // unlexable
                throw new SyntaxError(sprintf('Unexpected character "%s"', $expression[$cursor]), $cursor);
            }
        }

        $tokens[] = new Token(Token::EOF_TYPE, null, $cursor + 1);

        if (!empty($brackets)) {
            list($expect, $cur) = array_pop($brackets);
            throw new SyntaxError(sprintf('Unclosed "%s"', $expect), $cur);
        }

        return new TokenStream($tokens);
    }
}
