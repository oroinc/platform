<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Carbon\Carbon;
use Oro\Bundle\FilterBundle\Expression\Date\Parser;
use Oro\Bundle\FilterBundle\Expression\Date\Token;
use Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var Parser */
    private $parser;

    protected function setUp(): void
    {
        $localeSettings = $this->createMock(LocaleSettings::class);
        $localeSettings->expects($this->any())
            ->method('getTimeZone')
            ->willReturn('UTC');

        $this->parser = new Parser($localeSettings);
    }

    /**
     * @dataProvider parseProvider
     */
    public function testParse(array $tokens, mixed $expectedResult, ?string $expectedException = null)
    {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $result = $this->parser->parse($tokens);
        $this->assertEquals($expectedResult, $result);
    }

    public function parseProvider(): array
    {
        return [
            'should merge date and time' => [
                [
                    new Token(Token::TYPE_DATE, '2001-01-01'),
                    new Token(Token::TYPE_TIME, '23:00:00'),
                ],
                Carbon::parse('2001-01-01 23:00:00', 'UTC')
            ],
            'should merge date and time reverse mode' => [
                [
                    new Token(Token::TYPE_TIME, '23:00:00'),
                    new Token(Token::TYPE_DATE, '2001-01-01'),
                ],
                Carbon::parse('2001-01-01 23:00:00', 'UTC')
            ],
            'should process parentheses' => [
                [
                    new Token(Token::TYPE_PUNCTUATION, '('),
                    new Token(Token::TYPE_INTEGER, 2),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_INTEGER, 3),
                    new Token(Token::TYPE_PUNCTUATION, ')'),
                    new Token(Token::TYPE_OPERATOR, '-'),
                    new Token(Token::TYPE_INTEGER, 1),
                ],
                4
            ],
            'should check parentheses syntax' => [
                [
                    new Token(Token::TYPE_PUNCTUATION, '('),
                    new Token(Token::TYPE_INTEGER, 2),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_INTEGER, 3),
                    new Token(Token::TYPE_OPERATOR, '-'),
                    new Token(Token::TYPE_INTEGER, 1),
                ],
                null,
                \LogicException::class
            ],
            'should check parentheses syntax close w/o open' => [
                [
                    new Token(Token::TYPE_INTEGER, 2),
                    new Token(Token::TYPE_PUNCTUATION, ')'),
                ],
                null,
                \LogicException::class
            ],
            'one variable are allowed per expression' => [
                [
                    new Token(Token::TYPE_VARIABLE, 2),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_VARIABLE, 3),
                ],
                null,
                SyntaxException::class
            ]
        ];
    }
}
