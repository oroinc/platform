<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    /** @var CalendarEventManager */
    protected $manager;

    protected function setUp()
    {
        $this->doctrineHelper     = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade     = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarConfig     =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();

        $this->manager = new CalendarEventManager(
            $this->doctrineHelper,
            $this->securityFacade,
            $this->entityNameResolver,
            $this->calendarConfig
        );
    }

    public function testGetSystemCalendars()
    {
        $organizationId = 1;
        $calendars      = [
            ['id' => 123, 'name' => 'test', 'public' => true]
        ];

        $this->securityFacade->expects($this->once())
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getCalendarsQueryBuilder')
            ->with($organizationId)
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('select')
            ->with('sc.id, sc.name, sc.public')
            ->will($this->returnSelf());
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($calendars));

        $result = $this->manager->getSystemCalendars();
        $this->assertEquals($calendars, $result);
    }

    public function testGetUserCalendars()
    {
        $organizationId = 1;
        $userId         = 10;
        $user           = new User();
        $calendars      = [
            ['id' => 100, 'name' => null],
            ['id' => 200, 'name' => 'name2'],
        ];

        $this->securityFacade->expects($this->once())
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUserId')
            ->will($this->returnValue($userId));
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getUserCalendarsQueryBuilder')
            ->with($organizationId)
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('select')
            ->with('c.id, c.name')
            ->will($this->returnSelf());
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($calendars));

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($this->identicalTo($user))
            ->will($this->returnValue('name1'));

        $result = $this->manager->getUserCalendars();
        $this->assertEquals(
            [
                ['id' => 100, 'name' => 'name1'],
                ['id' => 200, 'name' => 'name2'],
            ],
            $result
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unexpected calendar alias: "unknown". CalendarId: 123.
     */
    public function testSetCalendarUnknownAlias()
    {
        $event = new CalendarEvent();

        $this->manager->setCalendar($event, 'unknown', 123);
    }

    public function testSetUserCalendar()
    {
        $calendarId = 123;
        $calendar   = new Calendar();
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->will($this->returnValue($calendar));

        $this->manager->setCalendar($event, Calendar::CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getCalendar());
    }

    public function testSetSameUserCalendar()
    {
        $calendarId = 123;
        $calendar   = new Calendar();
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();
        $event->setCalendar($calendar);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->manager->setCalendar($event, Calendar::CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getCalendar());
    }

    public function testSetSystemCalendar()
    {
        $calendarId = 123;
        $calendar   = new SystemCalendar();
        $calendar->setPublic(false);
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->will($this->returnValue($calendar));

        $this->manager->setCalendar($event, SystemCalendar::CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getSystemCalendar());
    }

    public function testSetPublicCalendar()
    {
        $calendarId = 123;
        $calendar   = new SystemCalendar();
        $calendar->setPublic(true);
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->will($this->returnValue($calendar));

        $this->manager->setCalendar($event, SystemCalendar::PUBLIC_CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getSystemCalendar());
    }

    public function testGetCalendarUid()
    {
        $this->assertEquals('test_123', $this->manager->getCalendarUid('test', 123));
    }

    public function testParseCalendarUid()
    {
        list($alias, $id) = $this->manager->parseCalendarUid('some_alias_123');
        $this->assertSame('some_alias', $alias);
        $this->assertSame(123, $id);
    }

    public function testChangeStatus()
    {
        $enum = new TestEnumValue(CalendarEvent::STATUS_ACCEPTED, CalendarEvent::STATUS_ACCEPTED);

        $statusRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $statusRepository->expects($this->any())
            ->method('find')
            ->with(CalendarEvent::STATUS_ACCEPTED)
            ->will($this->returnValue($enum));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->will($this->returnValue($statusRepository));

        $event = new CalendarEvent();
        $event->setRelatedAttendee(new Attendee());
        $this->assertNotEquals(CalendarEvent::STATUS_ACCEPTED, $event->getInvitationStatus());

        $this->manager->changeStatus($event, CalendarEvent::STATUS_ACCEPTED);
        $this->assertEquals(CalendarEvent::STATUS_ACCEPTED, $event->getInvitationStatus());
    }

    /**
     * @expectedException \Oro\Bundle\CalendarBundle\Exception\CalendarEventRelatedAttendeeNotFoundException
     * @expectedExceptionMessage Calendar event does not have relatedAttendee
     */
    public function testChangeStatusWithEmptyRelatedAttendee()
    {
        $event = new CalendarEvent();
        $this->manager->changeStatus($event, CalendarEvent::STATUS_ACCEPTED);
    }

    /**
     * @expectedException \Oro\Bundle\CalendarBundle\Exception\StatusNotFoundException
     * @expectedExceptionMessage Status "accepted" does not exists
     */
    public function testChangeStatusWithNonExistingStatus()
    {
        $statusRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $statusRepository->expects($this->any())
            ->method('find')
            ->with(CalendarEvent::STATUS_ACCEPTED)
            ->will($this->returnValue(null));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->will($this->returnValue($statusRepository));

        $event = (new CalendarEvent())
            ->setRelatedAttendee(new Attendee());

        $this->manager->changeStatus($event, CalendarEvent::STATUS_ACCEPTED);
    }
}
