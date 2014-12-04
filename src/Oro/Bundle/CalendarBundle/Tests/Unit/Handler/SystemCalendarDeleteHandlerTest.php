<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Handler;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Handler\SystemCalendarDeleteHandler;

class SystemCalendarDeleteHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var SystemCalendarDeleteHandler */
    protected $handler;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarConfig = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager        = $this->getMockBuilder('Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager        = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager->expects($this->any())
            ->method('getObjectManager')
            ->will($this->returnValue($objectManager));
        $ownerDeletionManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new SystemCalendarDeleteHandler();
        $this->handler->setCalendarConfig($this->calendarConfig);
        $this->handler->setSecurityFacade($this->securityFacade);
        $this->handler->setOwnerDeletionManager($ownerDeletionManager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Public calendars are disabled.
     */
    public function testHandleDeleteWhenPublicCalendarDisabled()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenPublicCalendarDeleteNotGranted()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
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

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenSystemCalendarDeleteNotGranted()
    {
        $calendar = new SystemCalendar();

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('DELETE', $this->identicalTo($calendar))
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }
}
