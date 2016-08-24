<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\Search\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Filter\SearchStringFilter;
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
        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        /* @var $filterUtility FilterUtility|\PHPUnit_Framework_MockObject_MockObject */
        $filterUtility = $this->getMock('Oro\Bundle\FilterBundle\Filter\FilterUtility');

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
        $query = $this->getMock(Query::class);

        $ds = $this->getMockBuilder(SearchFilterDatasourceAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ds->method('getWrappedSearchQuery')
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('andWhere')
            ->with('foo', Query::OPERATOR_EQUALS, 'bar');

        $this->filter->apply($ds, ['type' => TextFilterType::TYPE_EQUAL, 'value' => 'bar']);
    }
}
