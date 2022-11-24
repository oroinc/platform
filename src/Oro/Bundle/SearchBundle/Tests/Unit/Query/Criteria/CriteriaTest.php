<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Criteria;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;

class CriteriaTest extends \PHPUnit\Framework\TestCase
{
    public function testExpressionBuilder()
    {
        $this->assertInstanceOf(ExpressionBuilder::class, Criteria::expr());
    }

    public function testImplodeFieldType()
    {
        $this->assertEquals('type.name', Criteria::implodeFieldTypeName('type', 'name'));
    }

    /**
     * @dataProvider dataProviderForExplodeFieldType
     */
    public function testExplodeFieldType(string $field, array $expected)
    {
        $this->assertEquals($expected, Criteria::explodeFieldTypeName($field));
    }

    /**
     * @dataProvider dataProviderForSearchOperatorByComparisonOperator
     */
    public function testGetSearchOperatorByComparisonOperator(
        string $comparisonOperator,
        string $expectedQueryOperator
    ) {
        $queryOperator = Criteria::getSearchOperatorByComparisonOperator($comparisonOperator);
        $this->assertEquals($expectedQueryOperator, strtolower($queryOperator));
    }

    public function dataProviderForExplodeFieldType(): array
    {
        return [
            'text: contains separator' => [
                'field' => 'text.all_text',
                'expected' => ['text', 'all_text'],
            ],
            'integer: contains separator' => [
                'field' => 'integer.id',
                'expected' => ['integer', 'id'],
            ],
            'does not contain separator' => [
                'field' => 'string_field',
                'expected' => ['text', 'string_field'],
            ],
        ];
    }

    public function dataProviderForSearchOperatorByComparisonOperator(): array
    {
        return [
            [Comparison::LIKE, Query::OPERATOR_LIKE],
            [Comparison::NOT_LIKE, Query::OPERATOR_NOT_LIKE],
            [Comparison::CONTAINS, Query::OPERATOR_CONTAINS],
            [Comparison::NOT_CONTAINS, Query::OPERATOR_NOT_CONTAINS],
            [Comparison::NEQ, Query::OPERATOR_NOT_EQUALS],
            [Comparison::NIN, Query::OPERATOR_NOT_IN],
            [Comparison::STARTS_WITH, Query::OPERATOR_STARTS_WITH],
            [Comparison::EXISTS, Query::OPERATOR_EXISTS],
            [Comparison::NOT_EXISTS, Query::OPERATOR_NOT_EXISTS],
        ];
    }
}
