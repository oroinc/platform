<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Doctrine\Common\Collections\Expr\Comparison as BaseComparison;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchNumberFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SearchNumberFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private $filterName = 'filter-name';

    /**
     * @var string
     */
    private $dataName = 'field-name';

    /**
     * @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $formFactory;

    /**
     * @var FilterUtility|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filterUtility;

    /**
     * @var SearchNumberFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->filterUtility = $this->createMock(FilterUtility::class);

        $this->filter = new SearchNumberFilter($this->formFactory, $this->filterUtility);
        $this->filter->init($this->filterName, [
            FilterUtility::DATA_NAME_KEY => $this->dataName,
        ]);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->filter->apply($ds, ['type' => NumberFilterType::TYPE_GREATER_EQUAL, 'value' => 123]);
    }

    /**
     * @param string $filterType
     * @param string $comparisonOperator
     * @dataProvider applyDataProvider
     */
    public function testApply($filterType, $comparisonOperator)
    {
        $fieldName = 'decimal.field';
        $fieldValue = 100;

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $restriction = new BaseComparison($fieldName, $comparisonOperator, $fieldValue);
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($restriction, FilterUtility::CONDITION_AND);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);
        $this->assertTrue($this->filter->apply($ds, ['type' => $filterType, 'value' => $fieldValue]));
    }

    /**
     * @return array
     */
    public function applyDataProvider()
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

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

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
        $this->filterUtility->expects($this->any())
            ->method('getExcludeParams')
            ->willReturn([]);

        $expected = [
            'name' => 'filter-name',
            'label' => 'Filter-name',
            'choices' => [],
            'data_name' => 'field-name',
            'options' => [],
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
}
