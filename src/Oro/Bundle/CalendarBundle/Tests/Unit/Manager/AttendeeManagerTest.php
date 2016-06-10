<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class AttendeeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttendeeRelationManager */
    protected $attendeeRelationManager;

    /** @var AttendeeManager */
    protected $attendeeManager;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnCallback(function ($entity) {
                return $entity->getId();
            }));

        $this->attendeeRelationManager = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attendeeRelationManager->expects($this->any())
            ->method('getRelatedEntity')
            ->will($this->returnCallback(function ($attendee) {
                return $attendee->getUser();
            }));

        $this->attendeeManager = new AttendeeManager(
            $this->doctrineHelper,
            $this->attendeeRelationManager
        );
    }

    public function testLoadAttendeesByCalendarEventId()
    {
        $entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepository->expects($this->once())
            ->method('findBy')
            ->with(['calendarEvent' => 1])
            ->will($this->returnValue(new Attendee(1)));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:Attendee')
            ->will($this->returnValue($entityRepository));

        $this->attendeeManager->loadAttendeesByCalendarEventId(1);
    }

    public function testCreateAttendeeExclusions()
    {
        $attendees = [
            (new Attendee(1))
                ->setUser(new User(3)),
            new Attendee(2)
        ];

        $key = json_encode([
            'entityClass' => 'Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User',
            'entityId' => 3,
        ]);
        $val = json_encode([
            'entityClass' => 'Oro\Bundle\CalendarBundle\Entity\Attendee',
            'entityId' => 1
        ]);

        $result = $this->attendeeManager->createAttendeeExclusions($attendees);
        $this->assertEquals([$key => $val], $result);
    }
}
