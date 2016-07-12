<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Handler\CalendarEventDeleteHandler;

class CalendarEventDeleteHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailSendProcessor;

    /** @var RequestStack */
    protected $requestStack;

    /** @var CalendarEventDeleteHandler */
    protected $handler;

    protected function setUp()
    {
        $this->securityFacade     = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarConfig     = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager            = $this->getMockBuilder('Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailSendProcessor = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager            = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager->expects($this->any())
            ->method('getObjectManager')
            ->will($this->returnValue($objectManager));
        $ownerDeletionManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = new RequestStack();

        $this->handler = new CalendarEventDeleteHandler();
        $this->handler->setCalendarConfig($this->calendarConfig);
        $this->handler->setSecurityFacade($this->securityFacade);
        $this->handler->setOwnerDeletionManager($ownerDeletionManager);
        $this->handler->setEmailSendProcessor($this->emailSendProcessor);
        $this->handler->setRequestStack($this->requestStack);
    }

    public function testHandleDelete()
    {
        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue(new CalendarEvent()));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Public calendars are disabled.
     */
    public function testHandleDeleteWhenPublicCalendarDisabled()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenPublicCalendarEventManagementNotGranted()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_event_management')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage System calendars are disabled.
     */
    public function testHandleDeleteWhenSystemCalendarDisabled()
    {
        $calendar = new SystemCalendar();
        $event    = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenSystemCalendarEventManagementNotGranted()
    {
        $calendar = new SystemCalendar();
        $event    = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_event_management')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    public function testProcessDeleteShouldSendNotificationIfQueryIsPresent()
    {
        $this->requestStack->push(new Request(['notifyInvitedUsers' => true]));

        $event = new CalendarEvent();
        $this->emailSendProcessor->expects($this->once())
            ->method('sendDeleteEventNotification')
            ->with($event);

        $this->handler->processDelete($event, $this->manager->getObjectManager());
    }

    public function testProcessDeleteShouldSendNotificationIfRequestIsNull()
    {
        $event = new CalendarEvent();
        $this->emailSendProcessor->expects($this->once())
            ->method('sendDeleteEventNotification')
            ->with($event);

        $this->handler->processDelete($event, $this->manager->getObjectManager());
    }

    public function testProcessDeleteshouldNotSendNotification()
    {
        $this->requestStack->push(new Request());

        $this->emailSendProcessor->expects($this->never())
            ->method('sendDeleteEventNotification');

        $this->handler->processDelete(new CalendarEvent(), $this->manager->getObjectManager());
    }
}
