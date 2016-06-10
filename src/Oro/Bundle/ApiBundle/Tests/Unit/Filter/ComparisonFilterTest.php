<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Request\DataType;

class ComparisonFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ComparisonFilter */
    protected $comparisonFilter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $this->comparisonFilter->setSupportedOperators(
            [
                ComparisonFilter::EQ,
                ComparisonFilter::NEQ,
                ComparisonFilter::LT,
                ComparisonFilter::LTE,
                ComparisonFilter::GT,
                ComparisonFilter::GTE,
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Field must not be empty
     */
    public function testInvalidArgumentExceptionField()
    {
        $this->comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', ComparisonFilter::EQ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must not be NULL. Field: "fieldName".
     */
    public function testInvalidArgumentExceptionValue()
    {
        $this->comparisonFilter->setField('fieldName');
        $this->comparisonFilter->apply(new Criteria(), new FilterValue('path', null, ComparisonFilter::EQ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported operator: "operator". Field: "fieldName".
     */
    public function testInvalidArgumentExceptionOperator()
    {
        $this->comparisonFilter->setField('fieldName');
        $this->comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', 'operator'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported operator: "!=". Field: "fieldName".
     */
    public function testUnsupportedOperatorWhenOperatorsAreNotSpecified()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', ComparisonFilter::NEQ));
    }

    public function testFilterWhenOperatorsAreNotSpecified()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');

        $this->assertEquals(['='], $comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value', ComparisonFilter::EQ));

        $this->assertEquals(
            new Criteria(new Comparison('fieldName', Comparison::EQ, 'value')),
            $criteria
        );
    }

    public function testFilterWhenOnlyEqualOperatorIsSpecified()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setSupportedOperators([ComparisonFilter::EQ]);
        $comparisonFilter->setField('fieldName');

        $this->assertEquals(['='], $comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value', ComparisonFilter::EQ));

        $this->assertEquals(
            new Criteria(new Comparison('fieldName', Comparison::EQ, 'value')),
            $criteria
        );
    }

    /**
     * @param string      $fieldName
     * @param bool        $isArrayAllowed
     * @param FilterValue $filterValue
     * @param Criteria    $expectation
     *
     * @dataProvider testCaseProvider
     */
    public function testFilter($fieldName, $isArrayAllowed, $filterValue, $expectation)
    {
        $this->assertNull($this->comparisonFilter->getField());
        $this->comparisonFilter->setField($fieldName);
        $this->assertSame($fieldName, $this->comparisonFilter->getField());

        $this->comparisonFilter->setArrayAllowed(true); //setting to TRUE due parent should allow own check
        if ($filterValue) {
            $this->assertSame($isArrayAllowed, $this->comparisonFilter->isArrayAllowed($filterValue->getOperator()));
        }

        $this->assertEquals(['=', '!=', '<', '<=', '>', '>='], $this->comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $this->comparisonFilter->apply($criteria, $filterValue);

        $this->assertEquals($expectation, $criteria);
    }

    public function testCaseProvider()
    {
        return [
            'empty filter' => [
                'fieldName',  //fieldName
                true, //isArrayAllowed
                null, //filter
                new Criteria() //expectation
            ],
            'filter with default operator' => [
                'fieldName',
                true,
                new FilterValue('path', 'value'),
                new Criteria(new Comparison('fieldName', Comparison::EQ, 'value'))
            ],
            'EQ filter' => [
                'fieldName',
                true,
                new FilterValue('path', 'value', ComparisonFilter::EQ),
                new Criteria(new Comparison('fieldName', Comparison::EQ, 'value'))
            ],
            'NEQ filter' => [
                'fieldName',
                true,
                new FilterValue('path', 'value', ComparisonFilter::NEQ),
                new Criteria(new Comparison('fieldName', Comparison::NEQ, 'value'))
            ],
            'LT filter' => [
                'fieldName',
                false,
                new FilterValue('path', 'value', ComparisonFilter::LT),
                new Criteria(new Comparison('fieldName', Comparison::LT, 'value'))
            ],
            'LTE filter' => [
                'fieldName',
                false,
                new FilterValue('path', 'value', ComparisonFilter::LTE),
                new Criteria(new Comparison('fieldName', Comparison::LTE, 'value'))
            ],
            'GT filter' => [
                'fieldName',
                false,
                new FilterValue('path', 'value', ComparisonFilter::GT),
                new Criteria(new Comparison('fieldName', Comparison::GT, 'value'))
            ],
            'GTE filter' => [
                'fieldName',
                false,
                new FilterValue('path', 'value', ComparisonFilter::GTE),
                new Criteria(new Comparison('fieldName', Comparison::GTE, 'value'))
            ]
        ];
    }
}
