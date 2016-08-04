<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\CalendarBundle\EventListener\Datagrid\SystemCalendarGridListener;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SystemCalendarGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SystemCalendarGridListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));
        $this->calendarConfig =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();

        $this->listener = new SystemCalendarGridListener(
            $this->securityFacade,
            $this->calendarConfig
        );
    }

    public function testOnBuildBeforeBothPublicAndSystemCalendarsEnabled()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config   = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->never())
            ->method('offsetUnsetByPath');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    /**
     * @dataProvider disableCalendarProvider
     */
    public function testOnBuildBeforeAnyPublicOrSystemCalendarDisabled($isPublicSupported, $isSystemSupported)
    {
        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue($isPublicSupported));
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue($isSystemSupported));

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config   = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->at(0))
            ->method('offsetUnsetByPath')
            ->with('[columns][public]');
        $config->expects($this->at(1))
            ->method('offsetUnsetByPath')
            ->with('[filters][columns][public]');
        $config->expects($this->at(2))
            ->method('offsetUnsetByPath')
            ->with('[sorters][columns][public]');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    public function disableCalendarProvider()
    {
        return [
            [true, false],
            [false, true],
        ];
    }

    public function testOnBuildAfterBothPublicAndSystemGranted()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

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
            ->with('(sc.public = :public OR sc.organization = :organizationId)')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('public', true)
            ->will($this->returnSelf());

        $qb->expects($this->at(2))
            ->method('setParameter')
            ->with('organizationId', 1);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterBothPublicAndSystemEnabledButSystemNotGranted()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

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
            ->with('sc.public = :public')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('public', true);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterPublicDisabled()
    {
        $organizationId = 1;

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_view')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->once())
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));

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
            ->with('organizationId', $organizationId);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterSystemDisabled()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
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
            ->with('sc.public = :public')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('public', true);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterBothPublicAndSystemDisabled()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
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
            ->with('1 = 0')
            ->will($this->returnSelf());

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testGetActionConfigurationClosurePublicGranted()
    {
        $resultRecord = new ResultRecord(['public' => true]);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->will($this->returnValue(true));

        $closure = $this->listener->getActionConfigurationClosure();
        $this->assertEquals(
            [],
            $closure($resultRecord)
        );
    }

    public function testGetActionConfigurationClosurePublicNotGranted()
    {
        $resultRecord = new ResultRecord(['public' => true]);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->will($this->returnValue(false));

        $closure = $this->listener->getActionConfigurationClosure();
        $this->assertEquals(
            [
                'update' => false,
                'delete' => false,
            ],
            $closure($resultRecord)
        );
    }

    /**
     * @dataProvider getActionConfigurationClosureSystemProvider
     */
    public function testGetActionConfigurationClosureSystem($isUpdateGranted, $isDeleteGranted, $expected)
    {
        $resultRecord = new ResultRecord(['public' => false]);

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_system_calendar_update', null, $isUpdateGranted],
                        ['oro_system_calendar_delete', null, $isDeleteGranted],
                    ]
                )
            );

        $closure = $this->listener->getActionConfigurationClosure();
        $this->assertEquals(
            $expected,
            $closure($resultRecord)
        );
    }

    public function getActionConfigurationClosureSystemProvider()
    {
        return [
            [true, true, []],
            [true, false, ['delete' => false]],
            [false, true, ['update' => false]],
            [false, false, ['update' => false, 'delete' => false]],
        ];
    }
}
