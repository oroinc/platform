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

class ComparisonFilterTest extends \PHPUnit\Framework\TestCase
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
     * @expectedExceptionMessage The field must not be empty.
     */
    public function testEmptyFieldName()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', ComparisonFilter::EQ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The value must not be NULL. Field: "fieldName".
     */
    public function testNullValue()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', null, ComparisonFilter::EQ));
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException
     * @expectedExceptionMessage The operator "neq" is not supported.
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
            new Comparison('fieldName', Comparison::EQ, new Value('value')),
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
            new Comparison('fieldName', Comparison::EQ, new Value('value')),
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
     * @dataProvider filterDataProvider
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
            ComparisonFilter::EXISTS,
            ComparisonFilter::NEQ_OR_NULL,
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
    public function filterDataProvider()
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
                new Comparison('fieldName', Comparison::EQ, new Value('value'))
            ],
            'EQ filter'                    => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::EQ),
                new Comparison('fieldName', Comparison::EQ, new Value('value'))
            ],
            'NEQ filter'                   => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::NEQ),
                new Comparison('fieldName', Comparison::NEQ, new Value('value'))
            ],
            'LT filter'                    => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::LT),
                new Comparison('fieldName', Comparison::LT, new Value('value'))
            ],
            'LTE filter'                   => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::LTE),
                new Comparison('fieldName', Comparison::LTE, new Value('value'))
            ],
            'GT filter'                    => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::GT),
                new Comparison('fieldName', Comparison::GT, new Value('value'))
            ],
            'GTE filter'                   => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::GTE),
                new Comparison('fieldName', Comparison::GTE, new Value('value'))
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
                        new Comparison('fieldName', Comparison::GTE, new Value('value1')),
                        new Comparison('fieldName', Comparison::LTE, new Value('value2'))
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
                        new Comparison('fieldName', Comparison::LT, new Value('value1')),
                        new Comparison('fieldName', Comparison::GT, new Value('value2'))
                    ]
                )
            ],
            'EXISTS filter'                => [
                'fieldName',
                false,
                false,
                new FilterValue('path', true, ComparisonFilter::EXISTS),
                new Comparison('fieldName', 'EXISTS', new Value(true))
            ],
            'NOT EXISTS filter'            => [
                'fieldName',
                false,
                false,
                new FilterValue('path', false, ComparisonFilter::EXISTS),
                new Comparison('fieldName', 'EXISTS', new Value(false))
            ],
            'NEQ_OR_NULL filter'           => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::NEQ_OR_NULL),
                new Comparison('fieldName', 'NEQ_OR_NULL', new Value('value'))
            ],
            'CONTAINS filter'              => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::CONTAINS),
                new Comparison('fieldName', 'CONTAINS', new Value('value'))
            ],
            'NOT_CONTAINS filter'          => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::NOT_CONTAINS),
                new Comparison('fieldName', 'NOT_CONTAINS', new Value('value'))
            ],
            'STARTS_WITH filter'           => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::STARTS_WITH),
                new Comparison('fieldName', 'STARTS_WITH', new Value('value'))
            ],
            'NOT_STARTS_WITH filter'       => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::NOT_STARTS_WITH),
                new Comparison('fieldName', 'NOT_STARTS_WITH', new Value('value'))
            ],
            'ENDS_WITH filter'             => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::ENDS_WITH),
                new Comparison('fieldName', 'ENDS_WITH', new Value('value'))
            ],
            'NOT_ENDS_WITH filter'         => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::NOT_ENDS_WITH),
                new Comparison('fieldName', 'NOT_ENDS_WITH', new Value('value'))
            ]
        ];
    }

    /**
     * @param string      $fieldName
     * @param bool        $isArrayAllowed
     * @param bool        $isRangeAllowed
     * @param FilterValue $filterValue
     * @param Criteria    $expectation
     *
     * @dataProvider collectionFilterDataProvider
     */
    public function testCollectionFilter($fieldName, $isArrayAllowed, $isRangeAllowed, $filterValue, $expectation)
    {
        $supportedOperators = [
            ComparisonFilter::EQ,
            ComparisonFilter::NEQ,
            ComparisonFilter::EXISTS,
            ComparisonFilter::NEQ_OR_NULL,
            ComparisonFilter::CONTAINS,
            ComparisonFilter::NOT_CONTAINS
        ];

        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setSupportedOperators($supportedOperators);
        $comparisonFilter->setField($fieldName);
        $comparisonFilter->setCollection(true);

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
    public function collectionFilterDataProvider()
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
                new Comparison('fieldName', Comparison::MEMBER_OF, new Value('value'))
            ],
            'EQ filter'                    => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::EQ),
                new Comparison('fieldName', Comparison::MEMBER_OF, new Value('value'))
            ],
            'NEQ filter'                   => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::NEQ),
                new CompositeExpression(
                    'NOT',
                    [
                        new Comparison('fieldName', Comparison::MEMBER_OF, new Value('value'))
                    ]
                )
            ],
            'EQ filter for array'          => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], ComparisonFilter::EQ),
                new Comparison('fieldName', Comparison::MEMBER_OF, new Value(['value1', 'value2']))
            ],
            'NEQ filter for array'         => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], ComparisonFilter::NEQ),
                new CompositeExpression(
                    'NOT',
                    [
                        new Comparison('fieldName', Comparison::MEMBER_OF, new Value(['value1', 'value2']))
                    ]
                )
            ],
            'EQ filter for range'          => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), ComparisonFilter::EQ),
                new Comparison('fieldName', Comparison::MEMBER_OF, new Value(new Range('value1', 'value2')))
            ],
            'NEQ filter for range'         => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), ComparisonFilter::NEQ),
                new CompositeExpression(
                    'NOT',
                    [
                        new Comparison('fieldName', Comparison::MEMBER_OF, new Value(new Range('value1', 'value2')))
                    ]
                )
            ],
            'EXISTS filter'                => [
                'fieldName',
                false,
                false,
                new FilterValue('path', true, ComparisonFilter::EXISTS),
                new Comparison('fieldName', 'EMPTY', new Value(false))
            ],
            'NOT EXISTS filter'            => [
                'fieldName',
                false,
                false,
                new FilterValue('path', false, ComparisonFilter::EXISTS),
                new Comparison('fieldName', 'EMPTY', new Value(true))
            ],
            'NEQ_OR_NULL filter'           => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::NEQ_OR_NULL),
                new Comparison('fieldName', 'NEQ_OR_EMPTY', new Value('value'))
            ],
            'CONTAINS filter'              => [
                'fieldName',
                true,
                false,
                new FilterValue('path', 'value', ComparisonFilter::CONTAINS),
                new Comparison('fieldName', 'ALL_MEMBER_OF', new Value('value'))
            ],
            'NOT_CONTAINS filter'          => [
                'fieldName',
                true,
                false,
                new FilterValue('path', 'value', ComparisonFilter::NOT_CONTAINS),
                new CompositeExpression(
                    'NOT',
                    [
                        new Comparison('fieldName', 'ALL_MEMBER_OF', new Value('value'))
                    ]
                )
            ]
        ];
    }
}
