<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\EventListener\EntityListener;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $uow;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $coll;

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
        $this->coll = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Tests\Unit\FakePersistentCollection')
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
        $calendarMetadata   = new ClassMetadata(get_class($newCalendar));

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will(
                $this->returnValueMap(
                    [
                        ['Oro\Bundle\CalendarBundle\Entity\Calendar', $calendarMetadata],
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
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue([]));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarRepo));

        $this->em->expects($this->at(1))
            ->method('persist')
            ->with($this->equalTo($newCalendar));

        $this->uow->expects($this->at(1))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);

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

        $user->setId(1);
        $user->addOrganization($org);

        $newCalendar = new Calendar();
        $newCalendar->setOwner($user);
        $newCalendar->setOrganization($org);
        $calendarMetadata   = new ClassMetadata(get_class($newCalendar));

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will(
                $this->returnValueMap(
                    [
                        ['Oro\Bundle\CalendarBundle\Entity\Calendar', $calendarMetadata],
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
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue([$this->coll]));

        $this->coll->expects($this->any())
            ->method('getOwner')
            ->will($this->returnValue($user));
        $this->coll->expects($this->once())
            ->method('getInsertDiff')
            ->will($this->returnValue([$org]));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarRepo));

        $this->em->expects($this->at(2))
            ->method('persist')
            ->with($this->equalTo($newCalendar));

        $this->uow->expects($this->at(2))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);

        $this->listener->onFlush($args);
    }
}
