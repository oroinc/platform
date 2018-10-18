<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\Lexer;
use Oro\Bundle\FilterBundle\Expression\Date\Token;

class LexerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Lexer */
    protected $lexer;

    protected function setUp()
    {
        $translatorMock = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $providerMock   = $this->createMock('Oro\Bundle\FilterBundle\Provider\DateModifierProvider');
        $this->lexer    = new Lexer($translatorMock, $providerMock);
    }

    protected function tearDown()
    {
        unset($this->lexer);
    }

    /**
     * @dataProvider tokenizeProvider
     *
     * @param string      $input
     * @param array       $expectedTokens
     * @param null|string $expectedException
     */
    public function testTokenize($input, array $expectedTokens, $expectedException = null)
    {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $result = $this->lexer->tokenize($input);

        $this->assertInternalType('array', $result);
        $this->assertCount(count($expectedTokens), $result);

        foreach ($result as $key => $token) {
            $this->assertEquals($expectedTokens[$key], $token);
        }
    }

    /**
     * @return array
     */
    public function tokenizeProvider()
    {
        return [
            'should parse time token'                                     => [
                '01:00:00',
                [
                    new Token(Token::TYPE_TIME, '01:00:00')
                ]
            ],
            'should parse date time token'                                => [
                '2014-03-25 00:35',
                [
                    new Token(Token::TYPE_DATE, '2014-03-25'),
                    new Token(Token::TYPE_TIME, '00:35')
                ]
            ],
            'should parse date  token'                                    => [
                '2014-03-25',
                [
                    new Token(Token::TYPE_DATE, '2014-03-25'),
                ]
            ],
            'should tokenize variables'                                   => [
                '{{2}}',
                [
                    new Token(Token::TYPE_VARIABLE, 2)
                ]
            ],
            'should tokenize variables mixed with operators and integers' => [
                '{{2}} + 1',
                [
                    new Token(Token::TYPE_VARIABLE, 2),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_INTEGER, 1)
                ]
            ],
            'should process punctuation'                                  => [
                '(1 + 2) - 3',
                [
                    new Token(Token::TYPE_PUNCTUATION, '('),
                    new Token(Token::TYPE_INTEGER, 1),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_INTEGER, 2),
                    new Token(Token::TYPE_PUNCTUATION, ')'),
                    new Token(Token::TYPE_OPERATOR, '-'),
                    new Token(Token::TYPE_INTEGER, 3),
                ]
            ],
            'should check syntax errors'                                  => [
                '((1+3)',
                [],
                'Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException'
            ],
            'should check errors'                                         => [
                '1+3)',
                [],
                'Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException'
            ],
            'should not parse all string'                                 => [
                'some string',
                [],
                'Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException'
            ]
        ];
    }
}
