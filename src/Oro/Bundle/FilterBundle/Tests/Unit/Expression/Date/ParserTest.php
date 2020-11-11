<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Carbon\Carbon;
use Oro\Bundle\FilterBundle\Expression\Date\Parser;
use Oro\Bundle\FilterBundle\Expression\Date\Token;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var Parser */
    protected $parser;

    protected function setUp(): void
    {
        $localeSettingsMock = $this->createMock(LocaleSettings::class);
        $localeSettingsMock
            ->expects($this->any())
            ->method('getTimeZone')
            ->willReturn('UTC');

        $this->parser = new Parser($localeSettingsMock);
    }

    protected function tearDown(): void
    {
        unset($this->parser);
    }

    /**
     * @dataProvider parseProvider
     *
     * @param array $tokens
     * @param string $timeZone
     * @param mixed $expectedResult
     * @param null|string $expectedException
     */
    public function testParse($tokens, $timeZone, $expectedResult, $expectedException = null)
    {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $result = $this->parser->parse($tokens, false, $timeZone);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function parseProvider()
    {
        return [
            'Non system configuration time zone' => [
                [
                    new Token(Token::TYPE_DATE, '2001-01-01'),
                    new Token(Token::TYPE_TIME, '23:00:00'),
                ],
                'time zone' => 'America/Jamaica',
                Carbon::parse('2001-01-01 23:00:00', 'America/Jamaica')
            ],
            'should merge date and time' => [
                [
                    new Token(Token::TYPE_DATE, '2001-01-01'),
                    new Token(Token::TYPE_TIME, '23:00:00'),
                ],
                'time zone' => null,
                Carbon::parse('2001-01-01 23:00:00', 'UTC')
            ],
            'should merge date and time reverse mode' => [
                [
                    new Token(Token::TYPE_TIME, '23:00:00'),
                    new Token(Token::TYPE_DATE, '2001-01-01'),
                ],
                'time zone' => null,
                Carbon::parse('2001-01-01 23:00:00', 'UTC')
            ],
            'should process parentheses'                     => [
                [
                    new Token(Token::TYPE_PUNCTUATION, '('),
                    new Token(Token::TYPE_INTEGER, 2),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_INTEGER, 3),
                    new Token(Token::TYPE_PUNCTUATION, ')'),
                    new Token(Token::TYPE_OPERATOR, '-'),
                    new Token(Token::TYPE_INTEGER, 1),
                ],
                'time zone' => null,
                4
            ],
            'should check parentheses syntax'                => [
                [
                    new Token(Token::TYPE_PUNCTUATION, '('),
                    new Token(Token::TYPE_INTEGER, 2),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_INTEGER, 3),
                    new Token(Token::TYPE_OPERATOR, '-'),
                    new Token(Token::TYPE_INTEGER, 1),
                ],
                'time zone' => null,
                null,
                '\LogicException'
            ],
            'should check parentheses syntax close w/o open' => [
                [
                    new Token(Token::TYPE_INTEGER, 2),
                    new Token(Token::TYPE_PUNCTUATION, ')'),
                ],
                'time zone' => null,
                null,
                '\LogicException'
            ],
            'one variable are allowed per expression'        => [
                [
                    new Token(Token::TYPE_VARIABLE, 2),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_VARIABLE, 3),
                ],
                'time zone' => null,
                null,
                'Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException'
            ]
        ];
    }
}
