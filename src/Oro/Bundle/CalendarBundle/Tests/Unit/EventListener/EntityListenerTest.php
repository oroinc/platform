<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarConnection;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\EventListener\EntityListener;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $uow;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityManager;

    /** @var EntityListener */
    protected $listener;

    protected function setUp()
    {
        $this->em  = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new EntityListener($this->activityManager);
    }

    /**
     * Test new user creation
     */
    public function testOnFlushCreateUser()
    {
        $args = new OnFlushEventArgs($this->em);
        $user = new User();
        $org  = new Organization();
        $org->setId(1);
        $org->setName('test');

        $user->addOrganization($org);

        $newCalendar = new Calendar();
        $newCalendar->setOwner($user);
        $newCalendar->setOrganization($org);
        $newConnection = new CalendarConnection($newCalendar);
        $newCalendar->addConnection($newConnection);
        $calendarMetadata   = new ClassMetadata(get_class($newCalendar));
        $connectionMetadata = new ClassMetadata(get_class($newConnection));

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will(
                $this->returnValueMap(
                    [
                        ['Oro\Bundle\CalendarBundle\Entity\Calendar', $calendarMetadata],
                        ['Oro\Bundle\CalendarBundle\Entity\CalendarConnection', $connectionMetadata],
                    ]
                )
            );

        $calendarRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $calendarRepo->expects($this->any())
            ->method('findDefaultCalendar')
            ->will($this->returnValue(false));

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$user]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarRepo));

        $this->em->expects($this->at(2))
            ->method('persist')
            ->with($this->equalTo($newCalendar));
        $this->em->expects($this->at(3))
            ->method('persist')
            ->with($this->equalTo($newConnection));

        $this->uow->expects($this->at(1))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);
        $this->uow->expects($this->at(2))
            ->method('computeChangeSet')
            ->with($connectionMetadata, $newConnection);

        $this->listener->onFlush($args);
    }

    /**
     * Test existing user modification
     */
    public function testOnFlushUpdateUser()
    {
        $args = new OnFlushEventArgs($this->em);
        $user = new User();
        $org  = new Organization();
        $org->setId(1);
        $org->setName('test');

        $user->addOrganization($org);

        $newCalendar = new Calendar();
        $newCalendar->setOwner($user);
        $newCalendar->setOrganization($org);
        $newConnection = new CalendarConnection($newCalendar);
        $newCalendar->addConnection($newConnection);
        $calendarMetadata   = new ClassMetadata(get_class($newCalendar));
        $connectionMetadata = new ClassMetadata(get_class($newConnection));

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will(
                $this->returnValueMap(
                    [
                        ['Oro\Bundle\CalendarBundle\Entity\Calendar', $calendarMetadata],
                        ['Oro\Bundle\CalendarBundle\Entity\CalendarConnection', $connectionMetadata],
                    ]
                )
            );

        $calendarRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $calendarRepo->expects($this->any())
            ->method('findDefaultCalendar')
            ->will($this->returnValue(false));

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$user]));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarRepo));

        $this->em->expects($this->at(2))
            ->method('persist')
            ->with($this->equalTo($newCalendar));
        $this->em->expects($this->at(3))
            ->method('persist')
            ->with($this->equalTo($newConnection));

        $this->uow->expects($this->at(2))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);
        $this->uow->expects($this->at(3))
            ->method('computeChangeSet')
            ->with($connectionMetadata, $newConnection);

        $this->listener->onFlush($args);
    }

    /**
     * Test new calendar event creation
     */
    public function testOnFlushCreateCalendarEvent()
    {
        $args = new OnFlushEventArgs($this->em);
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 10);
        $calendar->setOwner($user);
        $event = new CalendarEvent();
        $event->setCalendar($calendar);

        $eventMetadata = new ClassMetadata(get_class($event));

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$event]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));

        $this->activityManager->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($event), $this->identicalTo($user))
            ->will($this->returnValue(true));
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->will($this->returnValue($eventMetadata));
        $this->uow->expects($this->once())
            ->method('computeChangeSet')
            ->with($this->identicalTo($eventMetadata), $this->identicalTo($event));

        $this->listener->onFlush($args);
    }

    /**
     * Test new calendar event creation when activity association already exist or disabled
     */
    public function testOnFlushCreateCalendarEventWithDisabledActivity()
    {
        $args = new OnFlushEventArgs($this->em);
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 10);
        $calendar->setOwner($user);
        $event = new CalendarEvent();
        $event->setCalendar($calendar);

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$event]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));

        $this->activityManager->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($event), $this->identicalTo($user))
            ->will($this->returnValue(false));
        $this->em->expects($this->never())
            ->method('getClassMetadata');
        $this->uow->expects($this->never())
            ->method('computeChangeSet');

        $this->listener->onFlush($args);
    }

    /**
     * Test moving existing calendar event to calendar of another user
     */
    public function testOnFlushUpdateCalendarEventAnotherUser()
    {
        $args  = new OnFlushEventArgs($this->em);
        $user1 = new User();
        ReflectionUtil::setId($user1, 1);
        $calendar1 = new Calendar();
        ReflectionUtil::setId($calendar1, 10);
        $calendar1->setOwner($user1);
        $event = new CalendarEvent();
        $event->setCalendar($calendar1);

        $user2 = new User();
        ReflectionUtil::setId($user1, 2);
        $calendar2 = new Calendar();
        ReflectionUtil::setId($calendar1, 20);
        $calendar2->setOwner($user2);

        $eventMetadata = new ClassMetadata(get_class($event));

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$event]));

        $this->uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($event))
            ->will($this->returnValue(['calendar' => [$calendar2, $calendar1]]));

        $this->activityManager->expects($this->once())
            ->method('replaceActivityTarget')
            ->with($this->identicalTo($event), $this->identicalTo($user2), $this->identicalTo($user1))
            ->will($this->returnValue(true));
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->will($this->returnValue($eventMetadata));
        $this->uow->expects($this->once())
            ->method('computeChangeSet')
            ->with($this->identicalTo($eventMetadata), $this->identicalTo($event));

        $this->listener->onFlush($args);
    }

    /**
     * Test moving existing calendar event to calendar of another user
     * when activity association already exist or disabled
     */
    public function testOnFlushUpdateCalendarEventAnotherUserWithDisabledActivity()
    {
        $args  = new OnFlushEventArgs($this->em);
        $user1 = new User();
        ReflectionUtil::setId($user1, 1);
        $calendar1 = new Calendar();
        ReflectionUtil::setId($calendar1, 10);
        $calendar1->setOwner($user1);
        $event = new CalendarEvent();
        $event->setCalendar($calendar1);

        $user2 = new User();
        ReflectionUtil::setId($user1, 2);
        $calendar2 = new Calendar();
        ReflectionUtil::setId($calendar1, 20);
        $calendar2->setOwner($user2);

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$event]));

        $this->uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($event))
            ->will($this->returnValue(['calendar' => [$calendar2, $calendar1]]));

        $this->activityManager->expects($this->once())
            ->method('replaceActivityTarget')
            ->with($this->identicalTo($event), $this->identicalTo($user2), $this->identicalTo($user1))
            ->will($this->returnValue(false));
        $this->em->expects($this->never())
            ->method('getClassMetadata');
        $this->uow->expects($this->never())
            ->method('computeChangeSet');

        $this->listener->onFlush($args);
    }

    /**
     * Test moving existing calendar event to another calendar of the same user
     */
    public function testOnFlushUpdateCalendarEventSameUser()
    {
        $args  = new OnFlushEventArgs($this->em);
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $calendar1 = new Calendar();
        ReflectionUtil::setId($calendar1, 10);
        $calendar1->setOwner($user);
        $event = new CalendarEvent();
        $event->setCalendar($calendar1);

        $calendar2 = new Calendar();
        ReflectionUtil::setId($calendar1, 20);
        $calendar2->setOwner($user);

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$event]));

        $this->uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($event))
            ->will($this->returnValue(['calendar' => [$calendar2, $calendar1]]));

        $this->activityManager->expects($this->never())
            ->method('replaceActivityTarget');
        $this->em->expects($this->never())
            ->method('getClassMetadata');
        $this->uow->expects($this->never())
            ->method('computeChangeSet');

        $this->listener->onFlush($args);
    }

    /**
     * Test existing calendar event modification (an event stays in the same calendar)
     */
    public function testOnFlushUpdateCalendarEvent()
    {
        $args  = new OnFlushEventArgs($this->em);
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $calendar1 = new Calendar();
        ReflectionUtil::setId($calendar1, 10);
        $calendar1->setOwner($user);
        $event = new CalendarEvent();
        $event->setCalendar($calendar1);

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([$event]));

        $this->uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($event))
            ->will($this->returnValue(['title' => ['old', 'new']]));

        $this->activityManager->expects($this->never())
            ->method('replaceActivityTarget');
        $this->em->expects($this->never())
            ->method('getClassMetadata');
        $this->uow->expects($this->never())
            ->method('computeChangeSet');

        $this->listener->onFlush($args);
    }
}
