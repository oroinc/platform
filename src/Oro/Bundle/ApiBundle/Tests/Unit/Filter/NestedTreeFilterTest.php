<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\NestedTreeFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class NestedTreeFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testForAssociation()
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('This filter is not supported for associations.');

        $filter = new NestedTreeFilter(DataType::INTEGER);
        $filter->apply(
            new Criteria(),
            new FilterValue('path.association', 'value', FilterOperator::GT)
        );
    }

    public function testUnsupportedOperator()
    {
        $this->expectException(InvalidFilterOperatorException::class);
        $this->expectExceptionMessage('The operator "neq" is not supported.');

        $filter = new NestedTreeFilter(DataType::INTEGER);
        $filter->apply(new Criteria(), new FilterValue('path', 'value', FilterOperator::NEQ));
    }

    public function testWithoutOperator()
    {
        $this->expectException(InvalidFilterOperatorException::class);
        $this->expectExceptionMessage('The operator "eq" is not supported.');

        $filter = new NestedTreeFilter(DataType::INTEGER);
        $filter->apply(new Criteria(), new FilterValue('path', 'value'));
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(FilterValue $filterValue, Comparison $expectation, string $field = null)
    {
        $supportedOperators = [
            FilterOperator::GT,
            FilterOperator::GTE
        ];

        $filter = new NestedTreeFilter(DataType::INTEGER);
        $filter->setSupportedOperators($supportedOperators);
        if ($field) {
            $filter->setField($field);
        }

        $criteria = new Criteria();
        $filter->apply($criteria, $filterValue);

        self::assertEquals($expectation, $criteria->getWhereExpression());
    }

    public function filterDataProvider(): array
    {
        return [
            'GT filter'               => [
                new FilterValue('path', 'value', FilterOperator::GT),
                new Comparison('', 'NESTED_TREE', new Value('value'))
            ],
            'GTE filter'              => [
                new FilterValue('path', 'value', FilterOperator::GTE),
                new Comparison('', 'NESTED_TREE_WITH_ROOT', new Value('value'))
            ],
            'GT filter (with field)'  => [
                new FilterValue('path', 'value', FilterOperator::GT),
                new Comparison('someField', 'NESTED_TREE', new Value('value')),
                'someField'
            ],
            'GTE filter (with field)' => [
                new FilterValue('path', 'value', FilterOperator::GTE),
                new Comparison('someField', 'NESTED_TREE_WITH_ROOT', new Value('value')),
                'someField'
            ]
        ];
    }
}
