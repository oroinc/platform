<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Expression;

use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Expression\Parser;
use Oro\Bundle\SearchBundle\Query\Expression\Token;
use Oro\Bundle\SearchBundle\Query\Expression\TokenStream;
use Oro\Bundle\SearchBundle\Query\Query;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    public function testParsesSelectKeyword()
    {
        $tokens = $this->generateTokens([
            [Token::KEYWORD_TYPE, 'select'],
            [Token::PUNCTUATION_TYPE, '('],
            [Token::STRING_TYPE, 'test1'],
            [Token::PUNCTUATION_TYPE, ','],
            [Token::STRING_TYPE, 'test2'],
            [Token::PUNCTUATION_TYPE, ')'],
            [Token::EOF_TYPE, '']
        ]);

        $parser = new Parser();
        $query = $parser->parse(new TokenStream($tokens));

        $this->assertContains('text.test1', $query->getSelect());
        $this->assertContains('text.test2', $query->getSelect());
    }

    private function generateTokens(array $elements): array
    {
        $result = [];

        foreach ($elements as $k => $element) {
            $result[] = new Token($element[0], $element[1], $k);
        }

        return $result;
    }

    public function testParseAggregateExpression()
    {
        $tokens = $this->generateTokens([
            [Token::KEYWORD_TYPE, Query::KEYWORD_AGGREGATE],
            [Token::STRING_TYPE, 'test_field'],
            [Token::STRING_TYPE, Query::AGGREGATE_FUNCTION_COUNT],
            [Token::KEYWORD_TYPE, Query::KEYWORD_AS],
            [Token::STRING_TYPE, 'test_name'],
            [Token::EOF_TYPE, '']
        ]);

        $parser = new Parser();
        $query = $parser->parse(new TokenStream($tokens));

        $this->assertEquals(
            ['test_name' =>
                ['field' => 'text.test_field', 'function' => Query::AGGREGATE_FUNCTION_COUNT, 'parameters' => []]
            ],
            $query->getAggregations()
        );
    }

    public function testParseAggregateExpressionFieldException()
    {
        $this->expectException(ExpressionSyntaxError::class);
        $this->expectExceptionMessage(
            'Aggregating field is expected.'
            . ' Unexpected token "keyword" of value "" ("string" expected with value "") around position 1.'
        );

        $tokens = $this->generateTokens([
            [Token::KEYWORD_TYPE, Query::KEYWORD_AGGREGATE],
            [Token::KEYWORD_TYPE, null],
        ]);

        $parser = new Parser();
        $parser->parse(new TokenStream($tokens));
    }

    public function testParseAggregateExpressionFunctionException()
    {
        $this->expectException(ExpressionSyntaxError::class);
        $this->expectExceptionMessage(
            'Aggregating function expected.'
            . ' Unexpected token "string" of value "test" ("string" expected with value "count, sum, max, min, avg")'
            . ' around position 2.'
        );

        $tokens = $this->generateTokens([
            [Token::KEYWORD_TYPE, Query::KEYWORD_AGGREGATE],
            [Token::STRING_TYPE, 'test_field'],
            [Token::STRING_TYPE, 'test'],
        ]);

        $parser = new Parser();
        $parser->parse(new TokenStream($tokens));
    }

    public function testParseAggregateExpressionNameException()
    {
        $this->expectException(ExpressionSyntaxError::class);
        $this->expectExceptionMessage(
            'Aggregating name is expected.'
            . ' Unexpected token "keyword" of value "select" ("string" expected with value "") around position 3.'
        );

        $tokens = $this->generateTokens([
            [Token::KEYWORD_TYPE, Query::KEYWORD_AGGREGATE],
            [Token::STRING_TYPE, 'test_field'],
            [Token::STRING_TYPE, Query::AGGREGATE_FUNCTION_COUNT],
            [Token::KEYWORD_TYPE, Query::KEYWORD_SELECT],
        ]);

        $parser = new Parser();
        $parser->parse(new TokenStream($tokens));
    }

    public function testParseAggregateExpressionUnsupportedFunctionException()
    {
        $this->expectException(ExpressionSyntaxError::class);
        $this->expectExceptionMessage(
            'Unsupported aggregating function "sum" for field type "text" around position 3.'
        );

        $tokens = $this->generateTokens([
            [Token::KEYWORD_TYPE, Query::KEYWORD_AGGREGATE],
            [Token::STRING_TYPE, 'test_field'],
            [Token::STRING_TYPE, Query::AGGREGATE_FUNCTION_SUM],
            [Token::KEYWORD_TYPE, Query::KEYWORD_SELECT],
        ]);

        $parser = new Parser();
        $parser->parse(new TokenStream($tokens));
    }
}
