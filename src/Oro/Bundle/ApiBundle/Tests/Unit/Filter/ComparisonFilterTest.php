<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Request\DataType;

class ComparisonFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testSetAndGetField()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        self::assertNull($comparisonFilter->getField());

        $fieldName = 'test';
        $comparisonFilter->setField($fieldName);
        self::assertSame($fieldName, $comparisonFilter->getField());
    }

    public function testSetAndGetSupportedOperators()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        self::assertEquals([ComparisonFilter::EQ], $comparisonFilter->getSupportedOperators());

        $supportedOperators = [ComparisonFilter::EQ, ComparisonFilter::NEQ];
        $comparisonFilter->setSupportedOperators($supportedOperators);
        self::assertEquals($supportedOperators, $comparisonFilter->getSupportedOperators());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The Field must not be empty.
     */
    public function testEmptyFieldName()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', ComparisonFilter::EQ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must not be NULL. Field: "fieldName".
     */
    public function testNullValue()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', null, ComparisonFilter::EQ));
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException
     * @expectedExceptionMessage The operator "!=" is not supported.
     */
    public function testUnsupportedOperator()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', ComparisonFilter::NEQ));
    }

    public function testFilterWhenOperatorsAreNotSpecified()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');

        self::assertEquals([ComparisonFilter::EQ], $comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value', ComparisonFilter::EQ));

        self::assertEquals(
            new Comparison('fieldName', Comparison::EQ, 'value'),
            $criteria->getWhereExpression()
        );
    }

    public function testFilterWhenOnlyEqualOperatorIsSpecified()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setSupportedOperators([ComparisonFilter::EQ]);
        $comparisonFilter->setField('fieldName');

        self::assertEquals([ComparisonFilter::EQ], $comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value', ComparisonFilter::EQ));

        self::assertEquals(
            new Comparison('fieldName', Comparison::EQ, 'value'),
            $criteria->getWhereExpression()
        );
    }

    /**
     * @param string      $fieldName
     * @param bool        $isArrayAllowed
     * @param bool        $isRangeAllowed
     * @param FilterValue $filterValue
     * @param Criteria    $expectation
     *
     * @dataProvider testCaseProvider
     */
    public function testFilter($fieldName, $isArrayAllowed, $isRangeAllowed, $filterValue, $expectation)
    {
        $supportedOperators = [
            ComparisonFilter::EQ,
            ComparisonFilter::NEQ,
            ComparisonFilter::LT,
            ComparisonFilter::LTE,
            ComparisonFilter::GT,
            ComparisonFilter::GTE,
            ComparisonFilter::CONTAINS,
            ComparisonFilter::NOT_CONTAINS,
            ComparisonFilter::STARTS_WITH,
            ComparisonFilter::NOT_STARTS_WITH,
            ComparisonFilter::ENDS_WITH,
            ComparisonFilter::NOT_ENDS_WITH
        ];

        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setSupportedOperators($supportedOperators);
        $comparisonFilter->setField($fieldName);

        $comparisonFilter->setArrayAllowed(true); // set to TRUE due parent should allow own check
        $comparisonFilter->setRangeAllowed(true); // set to TRUE due parent should allow own check
        if ($filterValue) {
            self::assertSame($isArrayAllowed, $comparisonFilter->isArrayAllowed($filterValue->getOperator()));
            self::assertSame($isRangeAllowed, $comparisonFilter->isRangeAllowed($filterValue->getOperator()));
        }

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, $filterValue);

        self::assertEquals($expectation, $criteria->getWhereExpression());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCaseProvider()
    {
        return [
            'empty filter'                 => [
                'fieldName', //fieldName
                true, // isArrayAllowed
                true, // isRangeAllowed
                null, // filter
                null // expectation
            ],
            'filter with default operator' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value'),
                new Comparison('fieldName', Comparison::EQ, 'value')
            ],
            'EQ filter'                    => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::EQ),
                new Comparison('fieldName', Comparison::EQ, 'value')
            ],
            'NEQ filter'                   => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::NEQ),
                new Comparison('fieldName', Comparison::NEQ, 'value')
            ],
            'LT filter'                    => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::LT),
                new Comparison('fieldName', Comparison::LT, 'value')
            ],
            'LTE filter'                   => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::LTE),
                new Comparison('fieldName', Comparison::LTE, 'value')
            ],
            'GT filter'                    => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::GT),
                new Comparison('fieldName', Comparison::GT, 'value')
            ],
            'GTE filter'                   => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::GTE),
                new Comparison('fieldName', Comparison::GTE, 'value')
            ],
            'EQ filter for array'          => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], ComparisonFilter::EQ),
                new Comparison('fieldName', Comparison::IN, new Value(['value1', 'value2']))
            ],
            'NEQ filter for array'         => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], ComparisonFilter::NEQ),
                new Comparison('fieldName', Comparison::NIN, new Value(['value1', 'value2']))
            ],
            'EQ filter for range'          => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), ComparisonFilter::EQ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('fieldName', Comparison::GTE, 'value1'),
                        new Comparison('fieldName', Comparison::LTE, 'value2')
                    ]
                )
            ],
            'NEQ filter for range'         => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), ComparisonFilter::NEQ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison('fieldName', Comparison::LT, 'value1'),
                        new Comparison('fieldName', Comparison::GT, 'value2')
                    ]
                )
            ],
            'CONTAINS filter'              => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::CONTAINS),
                new Comparison('fieldName', 'CONTAINS', 'value')
            ],
            'NOT_CONTAINS filter'          => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::NOT_CONTAINS),
                new Comparison('fieldName', 'NOT_CONTAINS', 'value')
            ],
            'STARTS_WITH filter'           => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::STARTS_WITH),
                new Comparison('fieldName', 'STARTS_WITH', 'value')
            ],
            'NOT_STARTS_WITH filter'       => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::NOT_STARTS_WITH),
                new Comparison('fieldName', 'NOT_STARTS_WITH', 'value')
            ],
            'ENDS_WITH filter'             => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::ENDS_WITH),
                new Comparison('fieldName', 'ENDS_WITH', 'value')
            ],
            'NOT_ENDS_WITH filter'         => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::NOT_ENDS_WITH),
                new Comparison('fieldName', 'NOT_ENDS_WITH', 'value')
            ]
        ];
    }
}
