<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Model\DashboardsModelCollection;

class DashboardsModelCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $expectedDashboards = array(new \stdClass(), new \stdClass(), new \stdClass());

        $collection = new DashboardsModelCollection($expectedDashboards);

        $iteration = 0;

        foreach ($collection as $actual) {
            $this->assertSame($expectedDashboards[$iteration++], $actual);
        }
    }

    public function testCount()
    {
        $expectedDashboards = array(new \stdClass(), new \stdClass(), new \stdClass());

        $collection = new DashboardsModelCollection($expectedDashboards);

        $this->assertCount(3, $collection);
    }

    public function testFindByName()
    {
        $expectedName  = 'expected name';

        $firstDashBoard = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Entity\Dashboard')
            ->disableOriginalConstructor()
            ->getMock();
        $firstDashBoardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $firstDashBoard->expects($this->any())->method('getName')->will($this->returnValue('unexpected name'));

        $secondDashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $secondDashboard = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Entity\Dashboard')
            ->disableOriginalConstructor()
            ->getMock();
        $secondDashboard->expects($this->any())->method('getName')->will($this->returnValue($expectedName));


        $firstDashBoardModel->expects($this->exactly(1))
            ->method('getDashboard')
            ->will($this->returnValue($firstDashBoard));
        $secondDashboardModel->expects($this->any())
            ->method('getDashboard')
            ->will($this->returnValue($secondDashboard));


        $expectedDashboards = array($firstDashBoardModel, $secondDashboardModel);

        $collection = new DashboardsModelCollection($expectedDashboards);

        $this->assertEquals($expectedName, $collection->findByName($expectedName)->getDashboard()->getName());
    }

    public function testFindById()
    {
        $expectedId  = '42';

        $firstDashBoard = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Entity\Dashboard')
            ->disableOriginalConstructor()
            ->getMock();
        $firstDashBoardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $firstDashBoard->expects($this->any())->method('getId')->will($this->returnValue('unexpected'));

        $secondDashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $secondDashboard = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Entity\Dashboard')
            ->disableOriginalConstructor()
            ->getMock();
        $secondDashboard->expects($this->any())->method('getId')->will($this->returnValue($expectedId));


        $firstDashBoardModel->expects($this->exactly(1))
            ->method('getDashboard')
            ->will($this->returnValue($firstDashBoard));
        $secondDashboardModel->expects($this->any())
            ->method('getDashboard')
            ->will($this->returnValue($secondDashboard));


        $expectedDashboards = array($firstDashBoardModel, $secondDashboardModel);

        $collection = new DashboardsModelCollection($expectedDashboards);

        $this->assertEquals($expectedId, $collection->findById($expectedId)->getDashboard()->getId());
    }
}
