<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Doctrine\Common\Collections\Expr\Comparison as DoctrineComparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchStringFilter;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;

class SearchStringFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchStringFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /* @var $formFactory FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
        $formFactory = $this->getMock(FormFactoryInterface::class);
        /* @var $filterUtility FilterUtility|\PHPUnit_Framework_MockObject_MockObject */
        $filterUtility = $this->getMock(FilterUtility::class);

        $this->filter = new SearchStringFilter($formFactory, $filterUtility);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid filter datasource adapter provided
     */
    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $ds = $this->getMock(FilterDatasourceAdapterInterface::class);
        $this->filter->apply($ds, ['type' => TextFilterType::TYPE_EQUAL, 'value' => 'bar']);
    }

    /**
     * @param string $filterType
     * @param string $comparisonOperator
     * @dataProvider applyDataProvider
     */
    public function testApply($filterType, $comparisonOperator)
    {
        $fieldName = 'field';
        $fieldValue = 'value';

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ds->expects($this->once())
            ->method('addRestriction')
            ->with($this->isInstanceOf('Doctrine\Common\Collections\Expr\Comparison'), FilterUtility::CONDITION_AND)
            ->willReturnCallback(
                function (DoctrineComparison $comparison) use ($fieldName, $comparisonOperator, $fieldValue) {
                    $this->assertEquals($fieldName, $comparison->getField());
                    $this->assertEquals($comparisonOperator, $comparison->getOperator());
                    $this->assertEquals($fieldValue, $comparison->getValue()->getValue());
                }
            );

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => $fieldName]);
        $this->filter->apply($ds, ['type' => $filterType, 'value' => $fieldValue]);
    }

    /**
     * @return array
     */
    public function applyDataProvider()
    {
        return [
            'contains' => [
                'filterType' => TextFilterType::TYPE_CONTAINS,
                'comparisonOperator' => Comparison::CONTAINS,
            ],
            'not contains' => [
                'filterType' => TextFilterType::TYPE_NOT_CONTAINS,
                'comparisonOperator' => Comparison::NOT_CONTAINS,
            ],
            'equal' => [
                'filterType' => TextFilterType::TYPE_EQUAL,
                'comparisonOperator' => Comparison::EQ,
            ],
        ];
    }
}
