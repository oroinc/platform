<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Datasource\Search;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\Search\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Filter\SearchStringFilter;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchFilterDatasourceAdapterTest extends \PHPUnit_Framework_TestCase
{
    /** @var IndexerQuery|\PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    protected function setUp()
    {
        $this->query = $this->getMockBuilder(IndexerQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuery'])
            ->getMock();
    }

    public function testUsingStringFilter()
    {
        $innerQuery = $this->getMock(Query::class);

        $this->query->method('getQuery')->willReturn($innerQuery);

        $innerQuery->expects($this->once())
            ->method('andWhere')
            ->with(
                'foo',
                Query::OPERATOR_CONTAINS,
                'bar'
            );

        $ds = new SearchFilterDatasourceAdapter($this->query);

        $formFactory = $this->getMock(FormFactoryInterface::class);

        $filterUtility = new FilterUtility();

        $stringFilter = new SearchStringFilter($formFactory, $filterUtility);
        $stringFilter->init('test', [
            FilterUtility::DATA_NAME_KEY => 'foo',
        ]);

        $stringFilter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'bar']);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testGroupBy()
    {
        $ds = new SearchFilterDatasourceAdapter($this->query);
        $ds->groupBy('name');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testAddGroupBy()
    {
        $ds = new SearchFilterDatasourceAdapter($this->query);
        $ds->addGroupBy('name');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testSetParameter()
    {
        $ds = new SearchFilterDatasourceAdapter($this->query);
        $ds->setParameter('key', 'value');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method currently not supported.
     */
    public function testGetFieldByAlias()
    {
        $ds = new SearchFilterDatasourceAdapter($this->query);
        $alias = $ds->getFieldByAlias('name');
    }
}
