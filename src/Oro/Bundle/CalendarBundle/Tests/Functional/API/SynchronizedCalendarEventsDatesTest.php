<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * @dbIsolation
 */
class SynchronizedCalendarEventsDatesTest extends AbstractUseCaseTestCase
{
    public function testSynchronizedCalendarEventsHasCorrectStartEndDates()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $start = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $end = $start->modify('+2 hour');

        $data = [
            'title' => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'start' => $start->format(DATE_RFC3339),
            'end' => $end->format(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => [],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => gmdate(DATE_RFC3339),
                'endTime' => null,
                'occurrences' => null,
                'timeZone' => 'UTC'
            ],
            'attendees' => [],
        ];
        $calendarEventId = $this->addCalendarEventViaAPI($data);
        $em->clear();

        $request = [
            'start'    => $start->modify('-1 hour')->format(DATE_RFC3339),
            'end'      => $end->modify('+1 day')->format(DATE_RFC3339),
            'calendar' => 1,
        ];
        $calendarEvents = $this->getAllCalendarEventsViaAPI($request);

        $this->assertNotEmpty($calendarEvents);
        $this->assertCount(2, $calendarEvents);

        $expectedStartAt = $start;
        $expectedEndAt = $end;
        foreach ($calendarEvents as $actualCalendarEvent) {
            $this->assertEquals($calendarEventId, $actualCalendarEvent['id']);

            $this->assertEquals($expectedStartAt->format(DATE_RFC3339), $actualCalendarEvent['start']);
            $this->assertEquals($expectedEndAt->format(DATE_RFC3339), $actualCalendarEvent['end']);

            $expectedStartAt = $expectedStartAt->modify('+1 day');
            $expectedEndAt = $expectedEndAt->modify('+1 day');
        }
    }
}
