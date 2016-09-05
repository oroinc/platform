<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class UserCalendarEventNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $reminderManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attendeeManager;

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
        $this->attendeeManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new UserCalendarEventNormalizer(
            $this->reminderManager,
            $this->securityFacade,
            $this->attendeeManager
        );
    }

    /**
     * @dataProvider getCalendarEventsProvider
     */
    public function testGetCalendarEvents($events, $attendees, $expected)
    {
        $calendarId = 123;

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($events));

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

        $this->attendeeManager->expects($this->any())
            ->method('getAttendeeListsByCalendarEventIds')
            ->will($this->returnCallback(function ($calendarEventIds) use ($attendees) {
                return array_intersect_key($attendees, array_flip($calendarEventIds));
            }));

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getCalendarEventsProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

        return [
            'no events'             => [
                'events'                 => [],
                'invitees'               => [],
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
                        'invitationStatus' => null,
                    ],
                ],
                'invitees'               => [1 => []],
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
                        'attendees'        => [],
                        'editable'         => true,
                        'removable'        => true,
                        'notifiable'       => false,
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
                        'invitationStatus' => null,
                    ],
                ],
                'attendees'                => [
                    1 => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                        ],
                    ],
                ],
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
                        'editable'         => true,
                        'removable'        => true,
                        'notifiable'       => true,
                        'attendees'     => [
                            [
                                'displayName' => 'user',
                                'email'       => 'user@example.com',
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider getCalendarEventProvider
     */
    public function testGetCalendarEvent($event, $calendarId, $expected)
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
     *
     * @return array
     */
    public function getCalendarEventProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

        return [
            'calendar not specified' => [
                'event'      => [
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
                    'invitationStatus' => '',
                    'invitedUsers'     => [],
                    'attendees'        => [],
                    'recurringEventId' => null,
                    'originalStart'    => null,
                    'isCancelled'      => false,
                ],
                'calendarId' => null,
                'expected'   => [
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
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => false,
                    'invitedUsers'     => [],
                    'attendees'        => [],
                    'recurringEventId' => null,
                    'originalStart'    => null,
                    'isCancelled'      => false,
                ]
            ],
            'own calendar'           => [
                'event'      => [
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
                    'invitationStatus' => null,
                    'attendees'        => [
                        [
                            'displayName' => 'user',
                            'email'       => 'user@example.com',
                            'status'      => Attendee::STATUS_NONE,
                        ]
                    ],
                    'recurringEventId' => null,
                    'originalStart'    => null,
                    'isCancelled'      => false,
                ],
                'calendarId' => 123,
                'expected'   => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate->format('c'),
                    'end'              => $endDate->format('c'),
                    'allDay'           => null,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null,
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => true,
                    'invitedUsers'     => [],
                    'attendees'        => [
                        [
                            'displayName' => 'user',
                            'email'       => 'user@example.com',
                            'userId'      => null,
                            'createdAt'   => null,
                            'updatedAt'   => null,
                            'status'      => Attendee::STATUS_NONE,
                            'type'        => null,
                        ]
                    ],
                    'recurringEventId' => null,
                    'originalStart'    => null,
                    'isCancelled'      => false,
                ]
            ],
            'another calendar'       => [
                'event'      => [
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
                    'invitationStatus' => CalendarEvent::STATUS_NONE,
                    'invitedUsers'     => []
                ],
                'calendarId' => 456,
                'expected'   => [
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
                    'attendees'        => [],
                    'editable'         => false,
                    'removable'        => false,
                    'notifiable'       => false,
                    'invitedUsers'     => [],
                    'recurringEventId' => null,
                    'originalStart'    => null,
                    'isCancelled'      => false,
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
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
        
        if (!empty($data['attendees'])) {
            foreach ($data['attendees'] as $attendeeData) {
                $attendee = new Attendee();
                $attendee->setEmail($attendeeData['email']);
                $attendee->setDisplayName($attendeeData['displayName']);

                if (array_key_exists('status', $attendeeData)) {
                    $status = new TestEnumValue($attendeeData['status'], $attendeeData['status']);
                    $attendee->setStatus($status);
                }

                $event->addAttendee($attendee);
            }
        }

        return $event;
    }
}
