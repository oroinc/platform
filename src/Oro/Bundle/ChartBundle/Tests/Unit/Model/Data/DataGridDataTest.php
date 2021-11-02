<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\DataGridData;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

class DataGridDataTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var DataGridData */
    private $data;

    protected function setUp(): void
    {
        $this->datagrid = $this->createMock(DatagridInterface::class);

        $this->data = new DataGridData($this->datagrid);
    }

    public function testToArray()
    {
        $rawData = [['foo' => 'bar']];

        $resultObject = $this->createMock(ResultsObject::class);
        $resultObject->expects($this->once())
            ->method('offsetGet')
            ->with('data')
            ->willReturn($rawData);

        $this->datagrid->expects($this->once())
            ->method('getData')
            ->willReturn($resultObject);

        $this->assertEquals($rawData, $this->data->toArray());
        $this->assertEquals($rawData, $this->data->toArray());
    }
}
