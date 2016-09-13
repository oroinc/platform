<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchStringFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\Search\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Query;

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
        $this->filter->init('foo', [
            FilterUtility::DATA_NAME_KEY => 'foo'
        ]);
    }

    public function testThrowsExceptionForWrongFilterDatasourceAdapter()
    {
        $ds = $this->getMock(FilterDatasourceAdapterInterface::class);

        $this->setExpectedException('\RuntimeException');

        $this->filter->apply($ds, ['type' => TextFilterType::TYPE_EQUAL, 'value' => 'bar']);
    }

    public function testAppliesConditionToQuery()
    {
        $criteria = $this->getMock(Criteria::class);

        $query = $this->getMock(Query::class);
        $query->method('getCriteria')
            ->willReturn($criteria);

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expressionBuilder = new ExpressionBuilder();
        $ds->method('expr')
            ->willReturn($expressionBuilder);

        $ds->method('getWrappedSearchQuery')
            ->will($this->returnValue($query));

        $criteria->expects($this->once())
            ->method('andWhere');

        $this->filter->apply($ds, ['type' => TextFilterType::TYPE_EQUAL, 'value' => 'bar']);
    }
}
