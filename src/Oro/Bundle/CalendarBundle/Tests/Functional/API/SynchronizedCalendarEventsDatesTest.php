<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class SynchronizedCalendarEventsDatesTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData']);
    }

    public function testSynchronizedCalendarEventsHasCorrectStartEndDates()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $start = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $end = $start->modify('+2 hour');

        $calendarEventId = $this->postCalendarEvent($start, $end);
        $em->clear();

        $calendarEvents = $this->getAllCalendarEventsViaAPI(
            $start->modify('-1 hour'),
            $end->modify('+1 day')
        );
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

    /**
     * Create new event
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     *
     * @return int
     */
    public function postCalendarEvent(\DateTimeInterface $start, \DateTimeInterface $end)
    {
        $request = [
            'title' => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'start' => $start->format(DATE_RFC3339),
            'end' => $end->format(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => 1,
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
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @param \DateTimeInterface $startDateFilter
     * @param \DateTimeInterface $endDateFilter
     *
     * @return array
     */
    public function getAllCalendarEventsViaAPI(\DateTimeInterface $startDateFilter, \DateTimeInterface $endDateFilter)
    {
        $request = [
            'start'    => $startDateFilter->format(DATE_RFC3339),
            'end'      => $endDateFilter->format(DATE_RFC3339),
            'calendar' => 1,
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);

        return $result;
    }
}
