<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;

/**
 * @dbIsolation
 */
class RecurringEventWithAttendeesAndDeletionTest extends AbstractUseCaseTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    const RECURRENCE_START_TIME = '2016-07-01T00:00:00P';
    const RECURRENCE_END_TIME = '2016-07-30T00:00:00P';

    const SEARCH_START_TIME = '2016-06-26T00:00:00P';
    const SEARCH_END_TIME = '2016-08-07T00:00:00P';

    public function testRecurringEventWithAttendeesAndDeletion()
    {
        $this->checkPreconditions();

        $user = $this->getReference('simple_user');
        $attendees = [
            [
                'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                'email'       => $user->getEmail(),
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $calendarEventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-07-02T09:00:00+00:00',
            'end'         => '2016-07-02T09:30:00+00:00',
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 1,
                'dayOfWeek'      => ['saturday'],
                'startTime'      => self::RECURRENCE_START_TIME,
                'occurrences'    => 5,
                'endTime'        => self::RECURRENCE_END_TIME,
            ],
            'attendees'   => $attendees,
        ];
        $recurringCalendarEventId = $this->addCalendarEventViaAPI($calendarEventData);

        $expectedCalendarEventData = [
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => $calendarEventData['start'],
                'end'         => $calendarEventData['end'],
                'attendees'   => [
                    [
                        'userId' => $user->getId()
                    ]
                ]
            ],
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => '2016-07-09T09:00:00+00:00',
                'end'         => '2016-07-09T09:30:00+00:00',
                'attendees'   => [
                    [
                        'userId' => $user->getId()
                    ]
                ]
            ],
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => '2016-07-16T09:00:00+00:00',
                'end'         => '2016-07-16T09:30:00+00:00',
                'attendees'   => [
                    [
                        'userId' => $user->getId()
                    ]
                ]
            ],
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => '2016-07-23T09:00:00+00:00',
                'end'         => '2016-07-23T09:30:00+00:00',
                'attendees'   => [
                    [
                        'userId' => $user->getId()
                    ]
                ]
            ],
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => '2016-07-30T09:00:00+00:00',
                'end'         => '2016-07-30T09:30:00+00:00',
                'attendees'   => [
                    [
                        'userId' => $user->getId()
                    ]
                ]
            ]
        ];
        $allEvents = $this->getAllCalendarEvents();
        $this->assertCalendarEvents($expectedCalendarEventData, $allEvents);
        $this->assertEventQuantityInDB(2);

        $exceptionCalendarEventData = [
            'originalStart'    => $calendarEventData['start'],
            'isCancelled'      => true,
            'title'            => $calendarEventData['title'],
            'description'      => $calendarEventData['description'],
            'allDay'           => false,
            'attendees'        => $attendees,
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'start'            => '2016-07-02T10:00:00P',
            'end'              => '2016-07-02T10:30:00P',
            'recurringEventId' => $recurringCalendarEventId,
        ];
        $this->addCalendarEventViaAPI($exceptionCalendarEventData);

        $allEvents = $this->getAllCalendarEvents();
        unset($expectedCalendarEventData[0]);
        $this->assertCalendarEvents($expectedCalendarEventData, $allEvents);
        $this->assertEventQuantityInDB(4);

        $allEvents = $this->getAllCalendarEvents();
        $result = array_filter(
            $allEvents,
            function (array $element) {
                return $element['isCancelled'] === true;
            }
        );
        $this->assertCount(0, $result);

        $canceledCalendarEvents = $this->getCanceledCalendarEvents();
        $this->assertCount(2, $canceledCalendarEvents);

        $this->deleteEventViaAPI($recurringCalendarEventId);
        $allEvents = $this->getAllCalendarEvents();
        $this->assertCount(0, $allEvents);
        $allEvents = $this->getAllCalendarEventsFromDB();
        $this->assertCount(0, $allEvents);
    }

    /**
     * @return array
     */
    protected function checkPreconditions()
    {
        $result = $this->getAllCalendarEvents();

        $this->assertEmpty($result);
    }

    /**
     * @param int $number
     */
    protected function assertEventQuantityInDB($number)
    {
        $allEvents = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findAll();

        $this->assertCount($number, $allEvents);
    }

    /**
     * @return array
     */
    protected function getAllCalendarEvents()
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => self::SEARCH_START_TIME,
            'end'         => self::SEARCH_END_TIME,
            'subordinate' => true,
        ];

        return $this->getAllCalendarEventsViaAPI($request);
    }

    /**
     * @return array|CalendarEvent[]
     */
    protected function getCanceledCalendarEvents()
    {
        $allEvents = $this->getEntityManager()
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['cancelled' => true]);

        return $allEvents;
    }

    /**
     * @return array|CalendarEvent[]
     */
    protected function getAllCalendarEventsFromDB()
    {
        $allEvents = $this->getEntityManager()
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findAll();

        return $allEvents;
    }
}
