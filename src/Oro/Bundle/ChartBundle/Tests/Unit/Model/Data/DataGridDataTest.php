<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\DataGridData;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataGridDataTest extends TestCase
{
    private DatagridInterface&MockObject $grid;
    private DataGridData $data;

    #[\Override]
    protected function setUp(): void
    {
        $this->grid = $this->createMock(DatagridInterface::class);

        $this->data = new DataGridData($this->grid);
    }

    public function testToArray(): void
    {
        $rawData = ['foo' => 'bar'];

        $resultRecord = $this->createMock(ResultRecord::class);
        $resultRecord->expects(self::once())
            ->method('getDataArray')
            ->willReturn($rawData);

        $datasource = $this->createMock(DatasourceInterface::class);
        $datasource->expects(self::once())
            ->method('getResults')
            ->willReturn([$resultRecord]);

        $this->grid->expects(self::once())
            ->method('getAcceptedDatasource')
            ->willReturn($datasource);

        self::assertSame([$rawData], $this->data->toArray());
        // Check cached data.
        self::assertSame([$rawData], $this->data->toArray());
    }
}
