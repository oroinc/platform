<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use PHPUnit\Framework\TestCase;

class SearchResultAfterTest extends TestCase
{
    public function testGetQuery(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultAfter($datagrid, $query, []);
        $this->assertEquals($query, $event->getQuery());
    }

    public function testGetDatagrid(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(SearchQueryInterface::class);

        $event = new SearchResultAfter($datagrid, $query, []);
        $this->assertEquals($datagrid, $event->getDatagrid());
    }

    public function testGetRecords(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(SearchQueryInterface::class);
        $records = ['first', 'second'];

        $event = new SearchResultAfter($datagrid, $query, $records);
        $this->assertEquals($records, $event->getRecords());
    }

    public function testSetRecords(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = $this->createMock(SearchQueryInterface::class);
        $records = ['first', 'second'];

        $event = new SearchResultAfter($datagrid, $query, []);
        $this->assertEmpty($event->getRecords());

        $event->setRecords($records);
        $this->assertEquals($records, $event->getRecords());
    }
}
