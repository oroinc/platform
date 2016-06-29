<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class AttendeeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CalendarEventRepository */
    protected $calendarEventRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttendeeRepository */
    protected $attendeeRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttendeeRelationManager */
    protected $attendeeRelationManager;

    /** @var AttendeeManager */
    protected $attendeeManager;

    public function setUp()
    {
        $this->calendarEventRepository = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attendeeRepository = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnCallback(function ($entity) {
                return $entity->getId();
            }));
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->will($this->returnValueMap([
                ['OroCalendarBundle:CalendarEvent', $this->calendarEventRepository],
                ['OroCalendarBundle:Attendee', $this->attendeeRepository],
            ]));

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
        $this->attendeeRepository->expects($this->once())
            ->method('findBy')
            ->with(['calendarEvent' => 1])
            ->will($this->returnValue(new Attendee(1)));

        $this->attendeeManager->loadAttendeesByCalendarEventId(1);
    }

    /**
     * @dataProvider createAttendeeExclusionsProvider
     */
    public function testCreateAttendeeExclusions($attendees, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->attendeeManager->createAttendeeExclusions($attendees)
        );
    }

    public function createAttendeeExclusionsProvider()
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

        return [
            'no attendees' => [
                [],
                [],
            ],
            'array of attendees' => [
                $attendees,
                [$key => $val],
            ],
            'collection of attendees' => [
                new ArrayCollection($attendees),
                [$key => $val],
            ],
        ];
    }

    /**
     * @dataProvider getAttendeeListsByCalendarEventIdsDataProvider
     */
    public function testGetAttendeeListsByCalendarEventIds(
        array $calendarEventIds,
        array $parentToChildren,
        array $queryResult,
        array $expectedResult
    ) {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['getQuery', 'getArrayResult'])
            ->getMock();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($queryResult));

        $this->calendarEventRepository->expects($this->once())
            ->method('getParentEventIds')
            ->with($calendarEventIds)
            ->will($this->returnValue($parentToChildren));

        $this->attendeeRepository->expects($this->once())
            ->method('createAttendeeListsQb')
            ->with(array_keys($parentToChildren))
            ->will($this->returnValue($qb));

        $this->attendeeRelationManager->expects($this->once())
            ->method('addRelatedEntityInfo')
            ->with($qb);

        $result = $this->attendeeManager->getAttendeeListsByCalendarEventIds($calendarEventIds);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getAttendeeListsByCalendarEventIdsDataProvider()
    {
        return [
            [
                [1, 2, 3],
                [
                    1 => [1],
                    4 => [2, 3],
                ],
                [
                    [
                        'calendarEventId' => 1,
                        'email' => 'first@example.com',
                    ],
                    [
                        'calendarEventId' => 4,
                        'email' => 'fourth@example.com',
                    ]
                ],
                [
                    1 => [
                        ['email' => 'first@example.com'],
                    ],
                    2 => [
                        ['email' => 'fourth@example.com'],
                    ],
                    3 => [
                        ['email' => 'fourth@example.com'],
                    ],
                ],
            ],
        ];
    }
}
