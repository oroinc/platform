<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\CalendarBundle\EventListener\Datagrid\SystemCalendarGridListener;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class SystemCalendarGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SystemCalendarGridListener */
    protected $listener;

    /** @var PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));

        $this->listener = new SystemCalendarGridListener($this->securityFacade);
    }

    public function testOnBuildAfterViewGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_view')
            ->will($this->returnValue(true));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('sc.organization = :organizationId')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('organizationId', 1);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterViewNotGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_view')
            ->will($this->returnValue(false));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('sc.organization = :organizationId')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('organizationId', 1);

        $qb->expects($this->at(2))
            ->method('andWhere')
            ->with('sc.public = :public')
            ->will($this->returnSelf());

        $qb->expects($this->at(3))
            ->method('setParameter')
            ->with('public', true);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }
}
