<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchResultBeforeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetQuery()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query    = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultBefore($datagrid, $query);
        $this->assertEquals($query, $event->getQuery());
    }

    public function testGetDatagrid()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query    = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultBefore($datagrid, $query);
        $this->assertEquals($datagrid, $event->getDatagrid());
    }
}
