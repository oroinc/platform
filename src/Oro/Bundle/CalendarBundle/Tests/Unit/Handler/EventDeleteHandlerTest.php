<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Handler;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Handler\EventDeleteHandler;

class EventDeleteHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var  EventDeleteHandler */
    protected $handler;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarConfig = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = $this->getMockBuilder('Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager->expects($this->any())
            ->method('getObjectManager')
            ->will($this->returnValue($objectManager));
        $ownerDeletionManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = (new EventDeleteHandler())
            ->setCalendarConfig($this->calendarConfig)
            ->setSecurityFacade($this->securityFacade);
        $this->handler->setOwnerDeletionManager($ownerDeletionManager);
    }

    public function testHandleDelete()
    {
        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue(new CalendarEvent()));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Public Calendars does not supported.
     */
    public function testHandleDeleteWhenPublicCalendarDisabled()
    {
        $calendar = (new SystemCalendar())
            ->setPublic(true);
        $event = (new CalendarEvent())
            ->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage System Calendars does not supported.
     */
    public function testHandleDeleteWhenSystemCalendarDisabled()
    {
        $calendar = (new SystemCalendar())
            ->setPublic(false);
        $event = (new CalendarEvent())
            ->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied to public calendar events management.
     */
    public function testHandleDeleteWhenPublicCalendarEventManagementNotGranted()
    {
        $calendar = (new SystemCalendar())
            ->setPublic(true);
        $event = (new CalendarEvent())
            ->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied to system calendar events management.
     */
    public function testHandleDeleteWhenSystemCalendarEventManagementNotGranted()
    {
        $calendar = (new SystemCalendar())
            ->setPublic(false);
        $event = (new CalendarEvent())
            ->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }
}
