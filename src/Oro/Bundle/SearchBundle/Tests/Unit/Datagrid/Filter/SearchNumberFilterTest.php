<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchNumberFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SearchNumberFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var SearchNumberFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new SearchNumberFilter($this->formFactory, new FilterUtility());
        $this->filter->init('test-filter', [
            FilterUtility::DATA_NAME_KEY => 'field_name'
        ]);
    }

    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply(
            $this->createMock(FilterDatasourceAdapterInterface::class),
            ['type' => NumberFilterType::TYPE_GREATER_EQUAL, 'value' => 123]
        );
    }

    /**
     * @dataProvider applyDataProvider
     */
    public function testApply(int $filterType, string $comparisonOperator)
    {
        $fieldName = 'decimal.field';
        $fieldValue = 100;

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);

        $restriction = new BaseComparison($fieldName, $comparisonOperator, $fieldValue);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($restriction, FilterUtility::CONDITION_AND);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);
        $this->assertTrue($this->filter->apply($ds, ['type' => $filterType, 'value' => $fieldValue]));
    }

    public function applyDataProvider(): array
    {
        return [
            '>=' => [
                'filterType' => NumberFilterType::TYPE_GREATER_EQUAL,
                'comparisonOperator' => Comparison::GTE,
            ],
            '>' => [
                'filterType' => NumberFilterType::TYPE_GREATER_THAN,
                'comparisonOperator' => Comparison::GT,
            ],
            '=' => [
                'filterType' => NumberFilterType::TYPE_EQUAL,
                'comparisonOperator' => Comparison::EQ,
            ],
            '!=' => [
                'filterType' => NumberFilterType::TYPE_NOT_EQUAL,
                'comparisonOperator' => Comparison::NEQ,
            ],
            '<=' => [
                'filterType' => NumberFilterType::TYPE_LESS_EQUAL,
                'comparisonOperator' => Comparison::LTE,
            ],
            '<' => [
                'filterType' => NumberFilterType::TYPE_LESS_THAN,
                'comparisonOperator' => Comparison::LT,
            ],
        ];
    }

    public function testApplyEmpty()
    {
        $fieldName = 'decimal.field';

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);

        $restriction = new Comparison($fieldName, Comparison::NOT_EXISTS, null);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($restriction, FilterUtility::CONDITION_AND);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);
        $this->assertTrue($this->filter->apply($ds, ['type' => FilterUtility::TYPE_EMPTY, 'value' => null]));
    }

    public function testApplyNotEmpty()
    {
        $fieldName = 'decimal.field';

        $ds = $this->createMock(SearchFilterDatasourceAdapter::class);

        $restriction = new Comparison($fieldName, Comparison::EXISTS, null);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($restriction, FilterUtility::CONDITION_AND);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);
        $this->assertTrue($this->filter->apply($ds, ['type' => FilterUtility::TYPE_NOT_EMPTY, 'value' => null]));
    }

    public function testGetMetadata()
    {
        $form = $this->createMock(FormInterface::class);
        $view = $this->createMock(FormView::class);

        $typeView = $this->createMock(FormView::class);
        $typeView->vars['choices'] = [];
        $view->vars['formatter_options'] = ['decimals' => 0, 'grouping' => false];
        $view->vars['array_separator'] = ',';
        $view->vars['array_operators'] = [9, 10];
        $view->vars['data_type'] = 'data_integer';
        $view->children['type'] = $typeView;

        $this->formFactory->expects($this->any())
            ->method('create')
            ->willReturn($form);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($view);

        $expected = [
            'name' => 'test-filter',
            'label' => 'Test-filter',
            'choices' => [],
            'lazy' => false,
            'formatterOptions' => [
                'decimals' => 0,
                'grouping' => false,
            ],
            'arraySeparator' => ',',
            'arrayOperators' => [9, 10],
            'dataType' => 'data_integer',
        ];
        $this->assertEquals($expected, $this->filter->getMetadata());
    }

    public function testPrepareData()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->filter->prepareData([]);
    }
}
