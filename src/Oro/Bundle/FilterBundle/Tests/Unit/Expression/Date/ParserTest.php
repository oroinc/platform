<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Expression\Date;

use Carbon\Carbon;
use Oro\Bundle\FilterBundle\Expression\Date\Parser;
use Oro\Bundle\FilterBundle\Expression\Date\Token;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var Parser */
    protected $parser;

    public function setUp()
    {
        $localeSettingsMock = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()->getMock();
        $localeSettingsMock->expects($this->any())->method('getTimeZone')
            ->will($this->returnValue('UTC'));

        $this->parser = new Parser($localeSettingsMock);
    }

    public function tearDown()
    {
        unset($this->parser);
    }

    /**
     * @dataProvider parseProvider
     *
     * @param array       $tokens
     * @param mixed       $expectedResult
     * @param null|string $expectedException
     */
    public function testParse($tokens, $expectedResult, $expectedException = null)
    {
        if (null !== $expectedException) {
            $this->setExpectedException($expectedException);
        }

        $result = $this->parser->parse($tokens);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function parseProvider()
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
                null,
                '\LogicException'
            ],
            'should check parentheses syntax close w/o open' => [
                [
                    new Token(Token::TYPE_INTEGER, 2),
                    new Token(Token::TYPE_PUNCTUATION, ')'),
                ],
                null,
                '\LogicException'
            ],
            'one variable are allowed per expression'        => [
                [
                    new Token(Token::TYPE_VARIABLE, 2),
                    new Token(Token::TYPE_OPERATOR, '+'),
                    new Token(Token::TYPE_VARIABLE, 3),
                ],
                null,
                'Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException'
            ]
        ];
    }
}
