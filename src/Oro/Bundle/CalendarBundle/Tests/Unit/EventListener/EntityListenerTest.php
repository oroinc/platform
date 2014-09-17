<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarConnection;
use Oro\Bundle\CalendarBundle\EventListener\EntityListener;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EntitySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $uow;

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

        $this->listener = new EntityListener();
    }

    /**
     * Test with create new user
     */
    public function testOnFlush()
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
        $calendarMetadata = new ClassMetadata(get_class($newCalendar));
        $connectionMetadata = new ClassMetadata(get_class($newConnection));

        $calendarRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $calendarRepo->expects($this->any())
            ->method('findByUserAndOrganization')
            ->will($this->returnValue(false));

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue(array($user)));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue(array()));

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
        $this->em->expects($this->at(4))
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarMetadata));
        $this->em->expects($this->at(5))
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:CalendarConnection')
            ->will($this->returnValue($connectionMetadata));

        $this->uow->expects($this->at(1))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);
        $this->uow->expects($this->at(2))
            ->method('computeChangeSet')
            ->with($connectionMetadata, $newConnection);

        $this->listener->onFlush($args);
    }

    /**
     * Copy of method "testOnFlush" but with update existing user
     */
    public function testOnFlushUpdate()
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
        $calendarMetadata = new ClassMetadata(get_class($newCalendar));
        $connectionMetadata = new ClassMetadata(get_class($newConnection));

        $calendarRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $calendarRepo->expects($this->any())
            ->method('findByUserAndOrganization')
            ->will($this->returnValue(false));

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue(array()));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue(array($user)));

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
        $this->em->expects($this->at(4))
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarMetadata));
        $this->em->expects($this->at(5))
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:CalendarConnection')
            ->will($this->returnValue($connectionMetadata));

        $this->uow->expects($this->at(2))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);
        $this->uow->expects($this->at(3))
            ->method('computeChangeSet')
            ->with($connectionMetadata, $newConnection);

        $this->listener->onFlush($args);
    }
}
