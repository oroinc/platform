<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Datasource;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\SearchBundle\Extension\IndexerQuery;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\Search\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Filter\SearchStringFilter;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SearchFilterDatasourceAdapterTest extends WebTestCase
{
    public function testUsingStringFilter()
    {
        $innerQuery = $this->getMock(Query::class);

        $query = $this->getMockBuilder(IndexerQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuery'])
            ->getMock();

        $query->method('getQuery')->willReturn($innerQuery);

        $innerQuery->expects($this->once())
            ->method('andWhere')
            ->with(
                'foo',
                Query::OPERATOR_CONTAINS,
                'bar'
            );

        $ds = new SearchFilterDatasourceAdapter($query);

        $formFactory = $this->getMock(FormFactoryInterface::class);

        $filterUtility = new FilterUtility();

        $stringFilter = new SearchStringFilter($formFactory, $filterUtility);
        $stringFilter->init('test', [
            FilterUtility::DATA_NAME_KEY => 'foo',
        ]);

        $stringFilter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'bar']);
    }
}
