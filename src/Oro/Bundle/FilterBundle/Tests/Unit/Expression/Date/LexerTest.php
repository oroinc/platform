<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Oro\Bundle\FilterBundle\Expression\Date\Lexer;
use Oro\Bundle\FilterBundle\Expression\Date\Token;
use Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class LexerTest extends \PHPUnit\Framework\TestCase
{
    private Lexer $lexer;

    protected function setUp(): void
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $providerMock   = $this->createMock(DateModifierProvider::class);
        $providerMock->expects(self::any())
            ->method('getVariableKey')
            ->willReturnCallback(function ($variable) {
                return DateModifierInterface::LABEL_VAR_PREFIX . DateModifierInterface::VAR_THIS_YEAR;
            });

        $this->lexer = new Lexer($translatorMock, $providerMock);
    }

    /**
     * @dataProvider tokenizeProvider
     *
     * @param string      $input
     * @param array       $expectedTokens
     * @param null|string $expectedException
     */
    public function testTokenize($input, array $expectedTokens, $expectedException = null): void
    {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $result = $this->lexer->tokenize($input);

        self::assertIsArray($result);
        self::assertCount(count($expectedTokens), $result);

        foreach ($result as $key => $token) {
            self::assertEquals($expectedTokens[$key], $token);
        }
    }

    public function tokenizeProvider(): array
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
                SyntaxException::class
            ],
            'should check errors'                                         => [
                '1+3)',
                [],
                SyntaxException::class
            ],
            'should not parse all string'                                 => [
                'some string',
                [],
                SyntaxException::class
            ]
        ];
    }
}
