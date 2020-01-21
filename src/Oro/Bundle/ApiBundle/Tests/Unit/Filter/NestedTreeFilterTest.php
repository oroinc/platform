<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\NestedTreeFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class NestedTreeFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\InvalidFilterException
     * @expectedExceptionMessage This filter is not supported for associations.
     */
    public function testForAssociation()
    {
        $filter = new NestedTreeFilter(DataType::INTEGER);
        $filter->apply(
            new Criteria(),
            new FilterValue('path.association', 'value', ComparisonFilter::GT)
        );
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException
     * @expectedExceptionMessage The operator "neq" is not supported.
     */
    public function testUnsupportedOperator()
    {
        $filter = new NestedTreeFilter(DataType::INTEGER);
        $filter->apply(new Criteria(), new FilterValue('path', 'value', ComparisonFilter::NEQ));
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException
     * @expectedExceptionMessage The operator "eq" is not supported.
     */
    public function testWithoutOperator()
    {
        $filter = new NestedTreeFilter(DataType::INTEGER);
        $filter->apply(new Criteria(), new FilterValue('path', 'value'));
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter($filterValue, $expectation, $field = null)
    {
        $supportedOperators = [
            ComparisonFilter::GT,
            ComparisonFilter::GTE
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

    public function filterDataProvider()
    {
        return [
            'GT filter'               => [
                new FilterValue('path', 'value', ComparisonFilter::GT),
                new Comparison('', 'NESTED_TREE', new Value('value'))
            ],
            'GTE filter'              => [
                new FilterValue('path', 'value', ComparisonFilter::GTE),
                new Comparison('', 'NESTED_TREE_WITH_ROOT', new Value('value'))
            ],
            'GT filter (with field)'  => [
                new FilterValue('path', 'value', ComparisonFilter::GT),
                new Comparison('someField', 'NESTED_TREE', new Value('value')),
                'someField'
            ],
            'GTE filter (with field)' => [
                new FilterValue('path', 'value', ComparisonFilter::GTE),
                new Comparison('someField', 'NESTED_TREE_WITH_ROOT', new Value('value')),
                'someField'
            ]
        ];
    }
}
