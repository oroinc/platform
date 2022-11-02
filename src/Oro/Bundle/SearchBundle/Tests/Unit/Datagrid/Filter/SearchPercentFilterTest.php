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
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchPercentFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormFactoryInterface;

class SearchPercentFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterDatasourceAdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datasource;

    /** @var SearchPercentFilter */
    private $filter;

    protected function setUp(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new SearchPercentFilter($formFactory, new FilterUtility());

        $this->datasource = $this->createMock(SearchFilterDatasourceAdapter::class);
    }

    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply(
            $this->createMock(FilterDatasourceAdapterInterface::class),
            [
                'type' => NumberRangeFilterType::TYPE_BETWEEN,
                'value' => 1.42,
                'value_end' => 1.55
            ]
        );
    }

    public function testApplyBetween()
    {
        $fieldName = 'decimal.field';

        $this->datasource->expects($this->exactly(2))
            ->method('addRestriction')
            ->withConsecutive(
                [new BaseComparison($fieldName, Comparison::GTE, 1.42), FilterUtility::CONDITION_AND, false],
                [new BaseComparison($fieldName, Comparison::LTE, 1.55), FilterUtility::CONDITION_AND, false]
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $this->assertTrue(
            $this->filter->apply(
                $this->datasource,
                [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => 142,
                    'value_end' => 155,
                ]
            )
        );
    }

    public function testApplyNotBetween()
    {
        $fieldName = 'decimal.field';

        $this->datasource->expects($this->once())
            ->method('addRestriction')
            ->with(
                new CompositeExpression(
                    FilterUtility::CONDITION_OR,
                    [
                        new CommonComparision('decimal.field', Comparison::LTE, new Value(1.42)),
                        new CommonComparision('decimal.field', Comparison::GTE, new Value(1.55)),
                    ]
                )
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);

        $this->assertTrue(
            $this->filter->apply(
                $this->datasource,
                [
                    'type' => NumberRangeFilterType::TYPE_NOT_BETWEEN,
                    'value' => 142,
                    'value_end' => 155,
                ]
            )
        );
    }

    public function testPrepareData()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->filter->prepareData([]);
    }
}
