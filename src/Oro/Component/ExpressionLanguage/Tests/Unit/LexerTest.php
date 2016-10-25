<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit;

use Oro\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\Token;
use Symfony\Component\ExpressionLanguage\TokenStream;

class LexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTokenizeData
     *
     * @param array $tokens
     * @param string $expression
     */
    public function testTokenize(array $tokens, $expression)
    {
        $tokens[] = new Token(Token::EOF_TYPE, null, strlen($expression) + 1);
        $lexer = new Lexer();
        $this->assertEquals(new TokenStream($tokens), $lexer->tokenize($expression));
    }

    /**
     * @return array
     */
    public function getTokenizeData()
    {
        return [
            [
                [new Token('name', 'a', 3)],
                '  a  ',
            ],
            [
                [new Token('name', 'a', 1)],
                'a',
            ],
            [
                [new Token('string', 'foo', 1)],
                '"foo"',
            ],
            [
                [new Token('number', '3', 1)],
                '3',
            ],
            [
                [new Token('operator', '+', 1)],
                '+',
            ],
            [
                [new Token('punctuation', '.', 1)],
                '.',
            ],
            [
                [
                    new Token('punctuation', '(', 1),
                    new Token('number', '3', 2),
                    new Token('operator', '+', 4),
                    new Token('number', '5', 6),
                    new Token('punctuation', ')', 7),
                    new Token('operator', '~', 9),
                    new Token('name', 'foo', 11),
                    new Token('punctuation', '(', 14),
                    new Token('string', 'bar', 15),
                    new Token('punctuation', ')', 20),
                    new Token('punctuation', '.', 21),
                    new Token('name', 'baz', 22),
                ],
                '(3 + 5) ~ foo("bar").baz',
            ],
            [
                [new Token('operator', '..', 1)],
                '..',
            ],
            [
                [new Token('string', '#foo', 1)],
                "'#foo'",
            ],
            [
                [new Token('string', '#foo', 1)],
                '"#foo"',
            ],
            [
                [
                    new Token('number', '3', 1),
                    new Token('operator', '=', 3),
                    new Token('number', '5', 5),
                ],
                '3 = 5',
            ],
        ];
    }
}
