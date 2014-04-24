<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\DataGridData;

class DataGridDataTest extends \PHPUnit_Framework_TestCase
{
    const DATA_GRID_NAME = 'test_grid_name';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataGridManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataGrid;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultObject;

    /**
     * @var DataGridData
     */
    protected $data;

    protected function setUp()
    {
        $this->dataGridManager = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface');
        $this->dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->resultObject = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject')
            ->disableOriginalConstructor()
            ->getMock();
        $this->data = new DataGridData($this->dataGridManager, self::DATA_GRID_NAME);
    }

    public function testToArray()
    {
        $rawData = array(array('foo' => 'bar'));

        $this->dataGridManager->expects($this->once())
            ->method('getDatagrid')
            ->with(self::DATA_GRID_NAME)
            ->will($this->returnValue($this->dataGrid));

        $this->dataGrid->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($this->resultObject));

        $this->resultObject->expects($this->once())
            ->method('offsetGet')
            ->with('data')
            ->will($this->returnValue($rawData));

        $this->assertEquals($rawData, $this->data->toArray());
        $this->assertEquals($rawData, $this->data->toArray());
    }
}
