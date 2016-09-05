<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

/**
 * @dbIsolation
 */
class CalendarEventWithEmptyAttendeesSavedTest extends AbstractUseCaseTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCalendarEventWasSavedEvenIfAttendeesRequestParameterIsEmpty()
    {
        $this->checkPreconditions();

        $calendarEventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-07-02T09:00:00+00:00',
            'end'         => '2016-07-02T09:30:00+00:00',
            'attendees'   => '',
        ];
        $recurringCalendarEventId = $this->addCalendarEventViaAPI($calendarEventData);
        $this->getEntityManager()->clear();

        $calendarEvent = $this->getCalendarEventById($recurringCalendarEventId);
        $this->assertNotNull($calendarEvent);

        $expectedCalendarEvents = [
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => $calendarEventData['start'],
                'end'         => $calendarEventData['end'],
                'attendees'   => []
            ],
        ];

        $actualCalendarEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEvents, $actualCalendarEvents);
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
}
