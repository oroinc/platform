<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\DataGridData;

class DataGridDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $datagrid;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultObject;

    /**
     * @var DataGridData
     */
    protected $data;

    protected function setUp()
    {
        $this->datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->resultObject = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject')
            ->disableOriginalConstructor()
            ->getMock();
        $this->data = new DataGridData($this->datagrid);
    }

    public function testToArray()
    {
        $rawData = array(array('foo' => 'bar'));

        $this->datagrid->expects($this->once())
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
