<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Responsible for tokenize date from string
 */
class Lexer
{
    const REGEXP_TIME     = '#(\d\d:\d\d(:\d\d)?)#';
    const REGEXP_DATETIME = '#((\d{4}\-\d{2}\-\d{2})[tT\s]*(\d\d:\d\d(:\d\d)?)?)#';
    const REGEXP_DAYMONTH = '#^\d{2}\-\d{2}$#';
    const REGEXP_VARIABLE = '#{{(\d+)}}#';
    const REGEXP_OPERATOR = '#\+|\-#';
    const REGEXP_INTEGER  = '#[0-9]+#';

    /** @var TranslatorInterface */
    private $translator;

    /** @var DateModifierProvider */
    private $provider;

    public function __construct(TranslatorInterface $translator, DateModifierProvider $provider)
    {
        $this->translator = $translator;
        $this->provider   = $provider;
    }

    /**
     * @param string $string
     *
     * @return Token[]
     * @throws SyntaxException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function tokenize(string $string): array
    {
        $cursor = 0;
        $tokens = $brackets = [];
        $end    = strlen($string);
        while ($cursor < $end) {
            if (' ' == $string[$cursor]) {
                ++$cursor;

                continue;
            }

            $current = $string[$cursor];
            if (preg_match(self::REGEXP_DATETIME . 'A', $string, $match, PREG_NO_ERROR, $cursor)) {
                // integers
                $tokens[] = new Token(Token::TYPE_DATE, $match[2]);
                if (!empty($match[3])) {
                    $tokens[] = new Token(Token::TYPE_TIME, $match[3]);
                }
                $cursor += strlen($match[0]);
            } elseif (preg_match(self::REGEXP_DAYMONTH . 'A', $string, $match, PREG_NO_ERROR, $cursor)) {
                $tokens[] = new Token(Token::TYPE_DAYMONTH, $match[0]);
                $cursor += strlen($match[0]);
            } elseif (preg_match(self::REGEXP_TIME . 'A', $string, $match, PREG_NO_ERROR, $cursor)) {
                $tokens[] = new Token(Token::TYPE_TIME, $match[0]);
                $cursor += strlen($match[0]);
            } elseif (preg_match(self::REGEXP_VARIABLE . 'A', $string, $match, PREG_NO_ERROR, $cursor)) {
                // variables
                $tokens[] = new Token(
                    Token::TYPE_VARIABLE,
                    $match[1],
                    $this->translator->trans($this->provider->getVariableKey($match[1]))
                );
                $cursor += strlen($match[0]);
            } elseif (preg_match(self::REGEXP_INTEGER . 'A', $string, $match, PREG_NO_ERROR, $cursor)) {
                // integers
                $tokens[] = new Token(Token::TYPE_INTEGER, $match[0]);
                $cursor += strlen($match[0]);
            } elseif (str_contains('(', $current)) {
                // opening bracket
                $brackets[] = $current;

                $tokens[] = new Token(Token::TYPE_PUNCTUATION, $current);
                ++$cursor;
            } elseif (str_contains(')', $current)) {
                // closing bracket
                if (null === array_pop($brackets)) {
                    throw new SyntaxException(sprintf('Unexpected "%s"', $current));
                }

                $tokens[] = new Token(Token::TYPE_PUNCTUATION, $current);
                ++$cursor;
            } elseif (preg_match(self::REGEXP_OPERATOR . 'A', $string, $match, PREG_NO_ERROR, $cursor)) {
                // operators
                $tokens[] = new Token(Token::TYPE_OPERATOR, $match[0]);
                ++$cursor;
            } else {
                // unlexable
                throw new SyntaxException(sprintf('Unexpected character "%s"', $current));
            }
        }

        if (!empty($brackets)) {
            $expect = array_pop($brackets);
            throw new SyntaxException(sprintf('Unclosed "%s"', $expect));
        }

        return $tokens;
    }
}
