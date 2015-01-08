<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer;

class UserCalendarEventNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $reminderManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var UserCalendarEventNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->reminderManager = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade  = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper  = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new UserCalendarEventNormalizer(
            $this->reminderManager,
            $this->securityFacade,
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider getCalendarEventsProvider
     */
    public function testGetCalendarEvents($events, $invitees, $expectedParentEventIds, $expected)
    {
        $calendarId = 123;

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($events));

        $this->setGetInvitedUsersExpectations($invitees, $expectedParentEventIds);

        if (!empty($events)) {
            $this->securityFacade->expects($this->exactly(2))
                ->method('isGranted')
                ->will(
                    $this->returnValueMap(
                        [
                            ['oro_calendar_event_update', null, true],
                            ['oro_calendar_event_delete', null, true],
                        ]
                    )
                );
        }

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    public function getCalendarEventsProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

        return [
            'no events'             => [
                'events'                 => [],
                'invitees'               => [],
                'expectedParentEventIds' => [],
                'expected'               => []
            ],
            'event without invitees' => [
                'events'                 => [
                    [
                        'calendar'         => 123,
                        'id'               => 1,
                        'title'            => 'test',
                        'description'      => null,
                        'start'            => $startDate,
                        'end'              => $endDate,
                        'allDay'           => false,
                        'backgroundColor'  => null,
                        'createdAt'        => null,
                        'updatedAt'        => null,
                        'parentEventId'    => null,
                        'invitationStatus' => null
                    ],
                ],
                'invitees'               => [],
                'expectedParentEventIds' => [1],
                'expected'               => [
                    [
                        'calendar'         => 123,
                        'id'               => 1,
                        'title'            => 'test',
                        'description'      => null,
                        'start'            => $startDate->format('c'),
                        'end'              => $endDate->format('c'),
                        'allDay'           => false,
                        'backgroundColor'  => null,
                        'createdAt'        => null,
                        'updatedAt'        => null,
                        'parentEventId'    => null,
                        'invitationStatus' => null,
                        'invitedUsers'     => [],
                        'editable'         => true,
                        'removable'        => true,
                        'notifiable'       => false
                    ],
                ]
            ],
            'event with invitees' => [
                'events'                 => [
                    [
                        'calendar'         => 123,
                        'id'               => 1,
                        'title'            => 'test',
                        'description'      => null,
                        'start'            => $startDate,
                        'end'              => $endDate,
                        'allDay'           => false,
                        'backgroundColor'  => null,
                        'createdAt'        => null,
                        'updatedAt'        => null,
                        'parentEventId'    => null,
                        'invitationStatus' => null
                    ],
                ],
                'invitees'               => [
                    ['parentEventId' => 1, 'eventId' => 2, 'userId' => 21],
                    ['parentEventId' => 1, 'eventId' => 3, 'userId' => 31],
                ],
                'expectedParentEventIds' => [1],
                'expected'               => [
                    [
                        'calendar'         => 123,
                        'id'               => 1,
                        'title'            => 'test',
                        'description'      => null,
                        'start'            => $startDate->format('c'),
                        'end'              => $endDate->format('c'),
                        'allDay'           => false,
                        'backgroundColor'  => null,
                        'createdAt'        => null,
                        'updatedAt'        => null,
                        'parentEventId'    => null,
                        'invitationStatus' => null,
                        'invitedUsers'     => [21, 31],
                        'editable'         => true,
                        'removable'        => true,
                        'notifiable'       => false
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider getCalendarEventProvider
     */
    public function testGetCalendarEvent($event, $calendarId, $invitees, $expectedParentEventIds, $expected)
    {
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_calendar_event_update', null, true],
                        ['oro_calendar_event_delete', null, true],
                    ]
                )
            );

        $this->setGetInvitedUsersExpectations($invitees, $expectedParentEventIds);

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with([$expected], 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $result = $this->normalizer->getCalendarEvent(
            $this->buildCalendarEvent($event),
            $calendarId
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getCalendarEventProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

        return [
            'calendar not specified' => [
                'event'                  => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate,
                    'end'              => $endDate,
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null
                ],
                'calendarId'             => null,
                'invitees'               => [],
                'expectedParentEventIds' => [1],
                'expected'               => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate->format('c'),
                    'end'              => $endDate->format('c'),
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null,
                    'invitedUsers'     => [],
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => false
                ]
            ],
            'own calendar'           => [
                'event'                  => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate,
                    'end'              => $endDate,
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null
                ],
                'calendarId'             => 123,
                'invitees'               => [],
                'expectedParentEventIds' => [1],
                'expected'               => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate->format('c'),
                    'end'              => $endDate->format('c'),
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null,
                    'invitedUsers'     => [],
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => false
                ]
            ],
            'another calendar'       => [
                'event'                  => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'start'            => $startDate,
                    'end'              => $endDate,
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null
                ],
                'calendarId'             => 456,
                'invitees'               => [],
                'expectedParentEventIds' => [1],
                'expected'               => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate->format('c'),
                    'end'              => $endDate->format('c'),
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null,
                    'invitedUsers'     => [],
                    'editable'         => false,
                    'removable'        => false,
                    'notifiable'       => false
                ]
            ],
        ];
    }

    protected function setGetInvitedUsersExpectations($invitees, $expectedParentEventIds)
    {
        if (!empty($expectedParentEventIds)) {
            $qb   = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->disableOriginalConstructor()
                ->getMock();
            $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository')
                ->disableOriginalConstructor()
                ->getMock();
            $repo->expects($this->once())
                ->method('getInvitedUsersByParentsQueryBuilder')
                ->with($expectedParentEventIds)
                ->will($this->returnValue($qb));
            $this->doctrineHelper->expects($this->once())
                ->method('getEntityRepository')
                ->with('OroCalendarBundle:CalendarEvent')
                ->will($this->returnValue($repo));

            $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
                ->disableOriginalConstructor()
                ->setMethods(['getArrayResult'])
                ->getMockForAbstractClass();
            $qb->expects($this->once())
                ->method('getQuery')
                ->will($this->returnValue($query));
            $query->expects($this->once())
                ->method('getArrayResult')
                ->will($this->returnValue($invitees));
        }
    }

    /**
     * @param array $data
     *
     * @return CalendarEvent
     */
    protected function buildCalendarEvent(array $data)
    {
        $event = new CalendarEvent();
        if (!empty($data['id'])) {
            $reflection = new \ReflectionProperty(get_class($event), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($event, $data['id']);
        }
        if (!empty($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (!empty($data['start'])) {
            $event->setStart($data['start']);
        }
        if (!empty($data['end'])) {
            $event->setEnd($data['end']);
        }
        if (!empty($data['calendar'])) {
            $calendar = new Calendar();
            $event->setCalendar($calendar);
            $reflection = new \ReflectionProperty(get_class($calendar), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($calendar, $data['calendar']);
        }

        return $event;
    }
}
