<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchResultAfterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetQuery()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query    = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultAfter($datagrid, $query, []);
        $this->assertEquals($query, $event->getQuery());
    }

    public function testGetDatagrid()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query    = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultAfter($datagrid, $query, []);
        $this->assertEquals($datagrid, $event->getDatagrid());
    }

    public function testGetRecords()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query    = $this->createMock(SearchQueryInterface::class);
        $records  = ['first', 'second'];

        $event = new SearchResultAfter($datagrid, $query, $records);
        $this->assertEquals($records, $event->getRecords());
    }

    public function testSetRecords()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query    = $this->createMock(SearchQueryInterface::class);
        $records  = ['first', 'second'];

        $event = new SearchResultAfter($datagrid, $query, []);
        $this->assertEmpty($event->getRecords());

        $event->setRecords($records);
        $this->assertEquals($records, $event->getRecords());
    }
}
