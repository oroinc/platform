<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
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
     * Test new user creation
     */
    public function testOnFlushCreateUser()
    {
        $args = new OnFlushEventArgs($this->em);

        $user = new User();
        $org1 = new Organization();
        ReflectionUtil::setId($org1, 1);
        $org2 = new Organization();
        ReflectionUtil::setId($org2, 2);
        $user->setOrganization($org1);
        $user->addOrganization($org1);
        $user->addOrganization($org2);

        $newCalendar1 = new Calendar();
        $newCalendar1->setOwner($user)->setOrganization($org1);
        $newCalendar2 = new Calendar();
        $newCalendar2->setOwner($user)->setOrganization($org2);

        $calendarMetadata = new ClassMetadata(get_class($newCalendar1));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$user]));
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue([]));

        $this->em->expects($this->at(1))
            ->method('persist')
            ->with($this->equalTo($newCalendar1));
        $this->em->expects($this->at(2))
            ->method('getClassMetadata')
            ->with('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->will($this->returnValue($calendarMetadata));
        $this->em->expects($this->at(3))
            ->method('persist')
            ->with($this->equalTo($newCalendar2));

        $this->uow->expects($this->at(1))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar1);
        $this->uow->expects($this->at(2))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar2);

        $this->listener->onFlush($args);
    }

    /**
     * Test existing user modification
     */
    public function testOnFlushUpdateUser()
    {
        $args = new OnFlushEventArgs($this->em);

        $user = new User();
        ReflectionUtil::setId($user, 123);
        $org = new Organization();
        ReflectionUtil::setId($org, 1);

        $coll = $this->getPersistentCollection($user, ['fieldName' => 'organizations'], [$org]);

        $newCalendar = new Calendar();
        $newCalendar->setOwner($user);
        $newCalendar->setOrganization($org);

        $calendarMetadata = new ClassMetadata(get_class($newCalendar));

        $calendarRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $calendarRepo->expects($this->any())
            ->method('findDefaultCalendar')
            ->will($this->returnValue(false));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([]));
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->will($this->returnValue([$coll]));

        $this->em->expects($this->at(1))
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarRepo));
        $this->em->expects($this->at(2))
            ->method('persist')
            ->with($this->equalTo($newCalendar));
        $this->em->expects($this->at(3))
            ->method('getClassMetadata')
            ->with('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->will($this->returnValue($calendarMetadata));

        $this->uow->expects($this->once())
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);

        $this->listener->onFlush($args);
    }

    /**
     * @param object $owner
     * @param array  $mapping
     * @param array  $items
     *
     * @return PersistentCollection
     */
    protected function getPersistentCollection($owner, array $mapping, array $items = [])
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $coll     = new PersistentCollection(
            $this->em,
            $metadata,
            new ArrayCollection($items)
        );

        $mapping['inversedBy'] = 'test';
        $coll->setOwner($owner, $mapping);

        return $coll;
    }
}
