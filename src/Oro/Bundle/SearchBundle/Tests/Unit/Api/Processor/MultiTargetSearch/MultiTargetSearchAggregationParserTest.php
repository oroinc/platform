<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Processor\MultiTargetSearch;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch\MultiTargetSearchAggregationParser;
use PHPUnit\Framework\TestCase;

class MultiTargetSearchAggregationParserTest extends TestCase
{
    /**
     * @dataProvider validAggregatesDataProvider
     */
    public function testValidAggregates(array $aggregates, array $expectedAggregations): void
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

        $parser = new MultiTargetSearchAggregationParser($searchFieldMappings, $fieldMappings);
        $result = $parser->parse($aggregates);
        self::assertEquals($expectedAggregations, $result);
    }

    public function validAggregatesDataProvider(): array
    {
        return [
            'empty' => [[], []],
            'field without mapping' => [
                ['field1 count'],
                ['field1Count' => [['field1', 'text', 'count']]]
            ],
            'field with mapping' => [
                ['intField2 count'],
                ['intField2Count' => [['search_int_field_2', 'integer', 'count']]]
            ],
            'custom field alias' => [
                ['field2 count myCount'],
                ['myCount' => [['field2', 'text', 'count']]]
            ],
            'several aggregations' => [
                ['strField2 count', 'intField2 sum'],
                [
                    'strField2Count' => [['search_str_field_2', 'text', 'count']],
                    'intField2Sum' => [['search_int_field_2', 'integer', 'sum']]
                ]
            ],
            'complex fields' => [
                ['strField3 count', 'intField3 sum'],
                [
                    'strField3Count' => [
                        ['search_str_field_3_1', 'text', 'count'],
                        ['search_str_field_3_2', 'text', 'count']
                    ],
                    'intField3Sum' => [
                        ['search_int_field_3_1', 'integer', 'sum'],
                        ['search_int_field_3_2', 'integer', 'sum']
                    ]
                ]
            ],
        ];
    }

    public function testNoFunction(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "field1" must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = 'field1';
        $parser = new MultiTargetSearchAggregationParser([], []);
        $parser->parse([$value]);
    }

    public function testNotSupportedFunction(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('The aggregating function "someFunction" is not supported.');

        $value = 'field1 someFunction';
        $parser = new MultiTargetSearchAggregationParser([], []);
        $parser->parse([$value]);
    }

    public function testNotSupportedFunctionForDataType(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('The aggregating function "sum" is not supported for the field type "text".');

        $value = 'field2 sum';
        $parser = new MultiTargetSearchAggregationParser([], []);
        $parser->parse([$value]);
    }

    public function testExtraElement(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "field1 count alias someOther" must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = 'field1 count alias someOther';
        $parser = new MultiTargetSearchAggregationParser([], []);
        $parser->parse([$value]);
    }

    public function testEmptyDefinition(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "" must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = '';
        $parser = new MultiTargetSearchAggregationParser([], []);
        $parser->parse([$value]);
    }

    public function testEmptyField(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value " " must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = ' ';
        $parser = new MultiTargetSearchAggregationParser([], []);
        $parser->parse([$value]);
    }

    public function testEmptyFunction(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "field1 " must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = 'field1 ';
        $parser = new MultiTargetSearchAggregationParser([], []);
        $parser->parse([$value]);
    }

    public function testEmptyAlias(): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage(
            'The value "field1 count " must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".'
        );

        $value = 'field1 count ';
        $parser = new MultiTargetSearchAggregationParser([], []);
        $parser->parse([$value]);
    }
}
