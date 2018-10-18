<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;
use Doctrine\Common\Collections\Expr\Comparison as CommonComparision;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchNumberRangeFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Symfony\Component\Form\FormFactoryInterface;

class SearchNumberRangeFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SearchNumberRangeFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /* @var $formFactory FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
        $formFactory = $this->createMock(FormFactoryInterface::class);
        /* @var $filterUtility FilterUtility|\PHPUnit\Framework\MockObject\MockObject */
        $filterUtility = $this->createMock(FilterUtility::class);

        $this->filter = new SearchNumberRangeFilter($formFactory, $filterUtility);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->filter->apply(
            $ds,
            [
                'type' => NumberRangeFilterType::TYPE_BETWEEN,
                'value' => 123,
                'value_end' => 155,
            ]
        );
    }

    public function testApplyBetween()
    {
        $fieldName = 'decimal.field';

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ds->expects($this->exactly(2))
            ->method('addRestriction')
            ->withConsecutive(
                [new BaseComparison($fieldName, Comparison::GTE, 123), FilterUtility::CONDITION_AND, false],
                [new BaseComparison($fieldName, Comparison::LTE, 155), FilterUtility::CONDITION_AND, false]
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);
        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => 123,
                    'value_end' => 155,
                ]
            )
        );
    }

    public function testApplyNotBetween()
    {
        $fieldName = 'decimal.field';

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ds->expects($this->exactly(1))
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    FilterUtility::CONDITION_OR,
                    [
                        new CommonComparision(
                            'decimal.field',
                            Comparison::LTE,
                            new Value(123)
                        ),
                        new CommonComparision(
                            'decimal.field',
                            Comparison::GTE,
                            new Value(155)
                        ),
                    ]
                )
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);
        $this->assertTrue(
            $this->filter->apply(
                $ds,
                [
                    'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value' => 123,
                    'value_end' => 155,
                ]
            )
        );
    }
}
