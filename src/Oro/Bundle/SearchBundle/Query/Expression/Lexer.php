<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Query;

class Lexer
{
    /** @var array */
    protected $keywords = [
        Query::KEYWORD_SELECT,
        Query::KEYWORD_FROM,
        Query::KEYWORD_WHERE,
        Query::KEYWORD_AGGREGATE,

        Query::KEYWORD_AND,
        Query::KEYWORD_OR,

        Query::KEYWORD_ORDER_BY,
        Query::KEYWORD_OFFSET,
        Query::KEYWORD_MAX_RESULTS,

        Query::KEYWORD_AS
    ];

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function tokenize($expression)
    {
        $expression = str_replace(["\r", "\n", "\t", "\v", "\f"], ' ', $expression);
        $cursor     = 0;
        $tokens     = $brackets = [];
        $end        = strlen($expression);

        while ($cursor < $end) {
            if (' ' == $expression[$cursor]) {
                ++$cursor;
                continue;
            }

            if (preg_match(
                '/(([1-2][0-9]{3})-(0[1-9]|1[0-2])-([0-2][1-9]|3[0-1])T([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]))' .
                '|(([1-2][0-9]{3})-(0[1-9]|1[0-2])-([0-2][1-9]|3[0-1]))/A',
                $expression,
                $match,
                null,
                $cursor
            )) {
                // date | datetime
                $tokens[] = new Token(Token::STRING_TYPE, $match[0], $cursor + 1);
                $cursor += strlen($match[0]);
            } elseif (preg_match('/[0-9]+(?:\.[0-9]+)?/A', $expression, $match, null, $cursor)) {
                // numbers
                $number = (float) $match[0];  // floats
                if (ctype_digit($match[0]) && $number <= PHP_INT_MAX) {
                    $number = (int) $match[0]; // integers lower than the maximum
                }
                $tokens[] = new Token(Token::NUMBER_TYPE, $number, $cursor + 1);
                $cursor += strlen($match[0]);
            } elseif (false !== strpos('([{', $expression[$cursor])) {
                // opening bracket
                $brackets[] = [$expression[$cursor], $cursor];
                $tokens[]   = new Token(Token::PUNCTUATION_TYPE, $expression[$cursor], $cursor + 1);
                ++$cursor;
            } elseif (false !== strpos(')]}', $expression[$cursor])) {
                // closing bracket
                if (empty($brackets)) {
                    throw new ExpressionSyntaxError(sprintf('Unexpected "%s"', $expression[$cursor]), $cursor);
                }
                list($expect, $cur) = array_pop($brackets);
                if ($expression[$cursor] != strtr($expect, '([{', ')]}')) {
                    throw new ExpressionSyntaxError(sprintf('Unclosed "%s"', $expect), $cur);
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
            } elseif (preg_match(
                '/and(?=[\s(])|\>\=|or(?=[\s(])|\<\=|in(?=[\s(])|\=|\!\=|\*|~|\!~|\>|\<|' .
                'exists(?=[\s(])|notexists(?=[\s(])|starts_with(?=[\s(])|like(?=[\s(])|notlike(?=[\s(])/A',
                $expression,
                $match,
                null,
                $cursor
            )) {
                // operators
                $tokens[] = new Token(Token::OPERATOR_TYPE, $match[0], $cursor + 1);
                $cursor += strlen($match[0]);
            } elseif (false !== strpos('.,?:', $expression[$cursor])) {
                // punctuation
                $tokens[] = new Token(Token::PUNCTUATION_TYPE, $expression[$cursor], $cursor + 1);
                ++$cursor;
            } elseif (preg_match(
                '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9@\-&=.:_\x7f-\xff]*/A',
                $expression,
                $match,
                null,
                $cursor
            )) {
                if (in_array($match[0], $this->keywords)) {
                    $tokens[] = new Token(Token::KEYWORD_TYPE, $match[0], $cursor + 1);
                } else {
                    $tokens[] = new Token(Token::STRING_TYPE, $match[0], $cursor + 1);
                }
                $cursor += strlen($match[0]);
            } else {
                // unlexable
                throw new ExpressionSyntaxError(sprintf('Unexpected character "%s"', $expression[$cursor]), $cursor);
            }
        }

        $tokens[] = new Token(Token::EOF_TYPE, null, $cursor + 1);

        if (!empty($brackets)) {
            list($expect, $cur) = array_pop($brackets);
            throw new ExpressionSyntaxError(sprintf('Unclosed "%s"', $expect), $cur);
        }

        return new TokenStream($tokens);
    }
}
