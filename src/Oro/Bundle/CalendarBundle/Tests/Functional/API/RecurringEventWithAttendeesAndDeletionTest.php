<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class RecurringEventWithAttendeesAndDeletionTest extends AbstractUseCaseTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRecurringEventWithAttendeesAndDeletion()
    {
        $this->checkPreconditions();

        /** @var User $simpleUser */
        $simpleUser = $this->getReference('simple_user');
        $attendees = [
            [
                'displayName' => sprintf('%s %s', $simpleUser->getFirstName(), $simpleUser->getLastName()),
                'email'       => $simpleUser->getEmail(),
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
                'startTime'      => '2016-07-01T00:00:00P',
                'occurrences'    => 5,
                'endTime'        => '2016-07-30T00:00:00P',
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
                        'userId' => $simpleUser->getId()
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
                        'userId' => $simpleUser->getId()
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
                        'userId' => $simpleUser->getId()
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
                        'userId' => $simpleUser->getId()
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
                        'userId' => $simpleUser->getId()
                    ]
                ]
            ]
        ];
        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEventData, $actualEvents);

        $simpleUserCalendar = $this->getUserCalendar($simpleUser);

        $actualEvents = $this->getAllCalendarEvents($simpleUserCalendar->getId());
        $expectedSimpleUserCalendarEventData = $this->changeExpectedDataCalendarId(
            $expectedCalendarEventData,
            $simpleUserCalendar->getId()
        );
        $this->assertCalendarEvents($expectedSimpleUserCalendarEventData, $actualEvents);

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
        $exceptionCalendarEventExceptionId = $this->addCalendarEventViaAPI($exceptionCalendarEventData);
        $this->assertCalendarEventAttendeesCount($exceptionCalendarEventExceptionId, 1);
        $this->assertCalendarEventAttendeesCount($exceptionCalendarEventExceptionId, 1);

        unset($expectedCalendarEventData[0]);
        unset($expectedSimpleUserCalendarEventData[0]);

        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEventData, $actualEvents);

        $actualEvents = $this->getAllCalendarEvents($simpleUserCalendar->getId());
        $this->assertCalendarEvents($expectedSimpleUserCalendarEventData, $actualEvents);

        $this->assertEventQuantityInDB(4);
        $this->assertCalendarEventAttendeesCount($exceptionCalendarEventExceptionId, 1);
        $this->assertCalendarEventAttendeesCount($exceptionCalendarEventExceptionId, 1);

        $canceledCalendarEvents = $this->getCanceledCalendarEvents();
        $this->assertCount(2, $canceledCalendarEvents);

        $this->deleteEventViaAPI($recurringCalendarEventId);

        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCount(0, $actualEvents);

        $actualEvents = $this->getAllCalendarEvents($simpleUserCalendar->getId());
        $this->assertCount(0, $actualEvents);

        $actualEvents = $this->getAllCalendarEventsFromDB();
        $this->assertCount(0, $actualEvents);
    }

    /**
     * @return array
     */
    protected function checkPreconditions()
    {
        $result = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);

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
     * @param int $calendarId
     *
     * @return array
     */
    protected function getAllCalendarEvents($calendarId)
    {
        $request = [
            'calendar'    => $calendarId,
            'start'       => '2016-06-26T00:00:00P',
            'end'         => '2016-08-07T00:00:00P',
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
