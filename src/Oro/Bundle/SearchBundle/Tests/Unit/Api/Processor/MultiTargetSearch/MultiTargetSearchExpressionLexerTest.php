<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Processor\MultiTargetSearch;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch\MultiTargetSearchExpressionLexer;
use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Expression\Parser;
use Oro\Bundle\SearchBundle\Query\Expression\TokenStream;
use Oro\Bundle\SearchBundle\Query\Query;
use PHPUnit\Framework\TestCase;

class MultiTargetSearchExpressionLexerTest extends TestCase
{
    private function convertTokenStreamToString(TokenStream $stream): string
    {
        $tokens = [];
        while (!$stream->isEOF()) {
            $tokens[] = $stream->current->value;
            $stream->next();
        }

        return implode(' ', $tokens);
    }

    /**
     * @dataProvider tokenizeDataProvider
     */
    public function testTokenize(string $searchExpression, string $expectedSearchExpression): void
    {
        $fieldMappings = [
            'intField2' => 'search_int_field_2',
            'intField3' => ['search_int_field_3_1', 'search_int_field_3_2'],
            'strField2' => 'search_str_field_2',
            'strField3' => ['search_str_field_3_1', 'search_str_field_3_2'],
        ];

        $searchFieldMappings = [
            'search_int_field_1' => ['type' => 'integer'],
            'search_int_field_2' => ['type' => 'integer'],
            'search_int_field_3_1' => ['type' => 'integer'],
            'search_int_field_3_2' => ['type' => 'integer'],
            'search_str_field_1' => ['type' => 'text'],
            'search_str_field_2' => ['type' => 'text'],
            'search_str_field_3_1' => ['type' => 'text'],
            'search_str_field_3_2' => ['type' => 'text'],
        ];

        $lexer = new MultiTargetSearchExpressionLexer($searchFieldMappings, $fieldMappings);
        $result = $lexer->tokenize($searchExpression);
        $resultSearchExpression = $this->convertTokenStreamToString($result);
        self::assertEquals($expectedSearchExpression, $resultSearchExpression);

        // check that the result expression does not hav syntax errors
        (new Parser())->parse($result, null, null, Query::KEYWORD_WHERE);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function tokenizeDataProvider(): array
    {
        return [
            'empty' => ['', ''],

            // one field
            'int field' => [
                'search_int_field_1 = 1',
                'integer search_int_field_1 = 1'
            ],
            'str field' => [
                'search_str_field_1 = a',
                'text search_str_field_1 = a'
            ],
            'renamed int field' => [
                'intField2 = 1',
                'integer search_int_field_2 = 1'
            ],
            'renamed str field' => [
                'strField2 = a',
                'text search_str_field_2 = a'
            ],
            'complex int field' => [
                'intField3 = 1',
                '( integer search_int_field_3_1 = 1 or integer search_int_field_3_2 = 1 )'
            ],
            'complex str field' => [
                'strField3 = a',
                '( text search_str_field_3_1 = a or text search_str_field_3_2 = a )'
            ],

            // one field with IN operator
            'int field (IN)' => [
                'search_int_field_1 in (1, 2)',
                'integer search_int_field_1 in ( 1 , 2 )'
            ],
            'str field (IN)' => [
                'search_str_field_1 in (a, b)',
                'text search_str_field_1 in ( a , b )'
            ],
            'renamed int field (IN)' => [
                'intField2 in (1, 2)',
                'integer search_int_field_2 in ( 1 , 2 )'
            ],
            'renamed str field (IN)' => [
                'strField2 in (a, b)',
                'text search_str_field_2 in ( a , b )'
            ],
            'complex int field (IN)' => [
                'intField3 in (1, 2)',
                '( integer search_int_field_3_1 in ( 1 , 2 ) or integer search_int_field_3_2 in ( 1 , 2 ) )'
            ],
            'complex str field (IN)' => [
                'strField3 in (a, b)',
                '( text search_str_field_3_1 in ( a , b ) or text search_str_field_3_2 in ( a , b ) )'
            ],

            // several fields
            'int field AND renamed int field' => [
                'search_int_field_1 = 1 and intField2 = 2',
                'integer search_int_field_1 = 1 and integer search_int_field_2 = 2'
            ],
            'str field AND renamed str field' => [
                'search_str_field_1 = a and strField2 = b',
                'text search_str_field_1 = a and text search_str_field_2 = b'
            ],
            'int field AND complex int field' => [
                'search_int_field_1 = 1 and intField3 = 3',
                'integer search_int_field_1 = 1'
                . ' and ( integer search_int_field_3_1 = 3 or integer search_int_field_3_2 = 3 )'
            ],
            'str field AND complex str field' => [
                'search_str_field_1 = a and strField3 = c',
                'text search_str_field_1 = a'
                . ' and ( text search_str_field_3_1 = c or text search_str_field_3_2 = c )'
            ],
            'renamed int field AND int field' => [
                'intField2 = 2 and search_int_field_1 = 1',
                'integer search_int_field_2 = 2 and integer search_int_field_1 = 1'
            ],
            'renamed str field AND str field' => [
                'strField2 = b and search_str_field_1 = a',
                'text search_str_field_2 = b and text search_str_field_1 = a'
            ],
            'complex int field AND int field' => [
                'intField3 = 3 and search_int_field_1 = 1',
                '( integer search_int_field_3_1 = 3 or integer search_int_field_3_2 = 3 )'
                . ' and integer search_int_field_1 = 1'
            ],
            'complex str field AND str field' => [
                'strField3 = c and search_str_field_1 = a',
                '( text search_str_field_3_1 = c or text search_str_field_3_2 = c )'
                . ' and text search_str_field_1 = a'
            ],

            // several fields with parentheses
            '(str field AND renamed str field)' => [
                '(search_str_field_1 = a and strField2 = b)',
                '( text search_str_field_1 = a and text search_str_field_2 = b )'
            ],
            '(str field) AND renamed str field' => [
                '(search_str_field_1 = a) and strField2 = b',
                '( text search_str_field_1 = a ) and text search_str_field_2 = b'
            ],
            'str field AND (renamed str field)' => [
                'search_str_field_1 = a and (strField2 = b)',
                'text search_str_field_1 = a and ( text search_str_field_2 = b )'
            ],
            '(str field) AND (renamed str field)' => [
                '(search_str_field_1 = a) and (strField2 = b)',
                '( text search_str_field_1 = a ) and ( text search_str_field_2 = b )'
            ],
            '((str field) AND (renamed str field))' => [
                '((search_str_field_1 = a) and (strField2 = b))',
                '( ( text search_str_field_1 = a ) and ( text search_str_field_2 = b ) )'
            ],

            // several fields with parentheses and IN operator
            '(str field (IN) AND renamed str field (IN))' => [
                '(search_str_field_1 in (a, b) and strField2 in (c, d))',
                '( text search_str_field_1 in ( a , b ) and text search_str_field_2 in ( c , d ) )'
            ],
            '(str field (IN)) AND renamed str field (IN)' => [
                '(search_str_field_1 in (a, b)) and strField2 in (c, d)',
                '( text search_str_field_1 in ( a , b ) ) and text search_str_field_2 in ( c , d )'
            ],
            'str field (IN) AND (renamed str field (IN))' => [
                'search_str_field_1 in (a, b) and (strField2 in (c, d))',
                'text search_str_field_1 in ( a , b ) and ( text search_str_field_2 in ( c , d ) )'
            ],
            '(str field (IN)) AND (renamed str field (IN))' => [
                '(search_str_field_1 in (a, b)) and (strField2 in (c, d))',
                '( text search_str_field_1 in ( a , b ) ) and ( text search_str_field_2 in ( c , d ) )'
            ],
            '((str field (IN)) AND (renamed str field (IN)))' => [
                '((search_str_field_1 in (a, b)) and (strField2 in (c, d)))',
                '( ( text search_str_field_1 in ( a , b ) ) and ( text search_str_field_2 in ( c , d ) ) )'
            ],
        ];
    }

    public function testTokenizeForInvalidExpression(): void
    {
        $this->expectException(ExpressionSyntaxError::class);
        $this->expectExceptionMessage('Unexpected ")" around position 13.');

        $lexer = new MultiTargetSearchExpressionLexer(
            ['search_int_field_1' => ['type' => 'integer']],
            ['intField1' => 'search_int_field_1']
        );
        $lexer->tokenize('intField1 = 1)');
    }

    public function testTokenizeForUnknownField(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('The field "unknownField" is not supported.');

        $lexer = new MultiTargetSearchExpressionLexer(
            ['search_int_field_1' => ['type' => 'integer']],
            ['intField1' => 'search_int_field_1']
        );
        $lexer->tokenize('unknownField = a');
    }
}
