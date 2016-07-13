<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestCalendarEventWithRepeatedExceptionTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData']);
    }

    public function testExceptionEventWillBeMarkedAsCanceledAfterOriginalEventWasUpdated()
    {
        $this->checkPreconditions();

        $start = new \DateTime();

        $end = new \DateTime();
        $end->modify('+1 hour');

        $recurringCalendarEvent = $this->postRecurringEvent($start, $end);

        /**
         * Update First time
         */
        $recurringEventException = $this->addRecurringEventException($recurringCalendarEvent->getId());
        /**
         * @todo add cancel route instead of delete
         */
        $this->deleteRecurringEventException($recurringEventException->getId());

        $start->modify('+1 hour');
        $end->modify('+1 hour');

        $this->changeRecurringEventTime($start, $end, $recurringCalendarEvent->getId());

        $this->assertRecurringCalendarEvents($recurringCalendarEvent, [$recurringEventException]);

        $secondRecurringEventException = $this->addRecurringEventException($recurringCalendarEvent->getId());
        $this->deleteRecurringEventException($secondRecurringEventException->getId());

        $start->modify('+1 hour');
        $end->modify('+1 hour');

        $this->changeRecurringEventTime($start, $end, $recurringCalendarEvent->getId());

        $this->assertRecurringCalendarEvents(
            $recurringCalendarEvent,
            [$recurringEventException, $secondRecurringEventException]
        );
    }

    /**
     * @param CalendarEvent $recurringCalendarEvent
     * @param CalendarEvent[] $recurringEventExceptions
     */
    protected function assertRecurringCalendarEvents(
        CalendarEvent $recurringCalendarEvent,
        array $recurringEventExceptions
    ) {
        $actualCalendarEvents = $this->getAllCalendarEventsViaApi($recurringCalendarEvent->getId());

        $expectedEventsCount = count($recurringEventExceptions) + 1;
        $this->assertCount($expectedEventsCount, $actualCalendarEvents);

        $this->assertEquals($recurringCalendarEvent->getId(), $actualCalendarEvents[0]['id']);
        $this->assertFalse($actualCalendarEvents[0]['isCancelled']);

        /**
         * Remove recurring calendar event from result array
         */
        array_shift($actualCalendarEvents);
        reset($recurringEventExceptions);
        foreach ($actualCalendarEvents as $actualRecurringCalendarEvent) {
            $recurringEventException = current($recurringEventExceptions);
            $this->assertEquals($recurringEventException->getId(), $actualRecurringCalendarEvent['id']);
            $this->assertTrue(
                $actualRecurringCalendarEvent['isCancelled'],
                sprintf(
                    'Recurrent Event Exception[id: %s; start: %s; end: %s] is not canceled',
                    $actualRecurringCalendarEvent['id'],
                    $actualRecurringCalendarEvent['start'],
                    $actualRecurringCalendarEvent['end']
                )
            );

            next($recurringEventExceptions);
        }
    }

    protected function checkPreconditions()
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => date(DATE_RFC3339, strtotime('-1 day')),
            'end'         => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => false,
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($result);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return CalendarEvent
     */
    protected function postRecurringEvent(\DateTime $start, \DateTime $end)
    {
        $request = [
            'title'       => 'Test Recurring Event splitting',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => $start->format(DATE_RFC3339),
            'end'         => $end->format(DATE_RFC3339),
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 1,
                'dayOfWeek'      => ['saturday'],
                'startTime'      => gmdate(DATE_RFC3339),
                'occurrences'    => 5,
                'endTime'        => gmdate(DATE_RFC3339),
            ],
            'attendees'   => [
                [
                    'displayName' => 'user@example.com',
                    'email'       => 'user@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => 'admin@example.com',
                    'email'       => 'admin@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
            ],
        ];

        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        /** @var CalendarEvent $event */
        $event = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);

        return $event;
    }

    /**
     * @param int $calendarEventId
     *
     * @return CalendarEvent
     */
    protected function addRecurringEventException($calendarEventId)
    {
        $request = [
            'originalStart'    => gmdate(DATE_RFC3339),
            'isCancelled'      => false,
            'title'            => 'Test Recurring Event splitting',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'start'            => gmdate(DATE_RFC3339),
            'end'              => gmdate(DATE_RFC3339),
            'recurringEventId' => $calendarEventId,
            'attendees'        => [
                [
                    'displayName' => 'admin@example.com',
                    'email'       => 'admin@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => 'user@example.com',
                    'email'       => 'user@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
            ],
        ];

        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);
        $response = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($response);
        $this->assertTrue(isset($response['id']));

        $events = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['id' => $response['id']]);

        $this->assertCount(1, $events);

        /** @var CalendarEvent $event */
        $event = reset($events);

        $this->assertNotNull($event);
        $this->assertFalse($event->isCancelled());

        return $event;
    }

    /**
     * @param int $calendarEventId
     */
    protected function deleteRecurringEventException($calendarEventId)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $calendarEventId])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 204);
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param  int      $calendarEventId
     */
    protected function changeRecurringEventTime(\DateTime $startDate, \DateTime $endDate, $calendarEventId)
    {
        $request = [
            'start' => $startDate->format(DATE_RFC3339),
            'end'   => $endDate->format(DATE_RFC3339),
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $calendarEventId]),
            $request
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);
    }

    /**
     * @param int $calendarEventId
     *
     * @return array
     */
    public function getAllCalendarEventsViaApi($calendarEventId)
    {
        $request = [
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'limit'            => 500,
            'page'             => 1,
            'recurringEventId' => $calendarEventId,
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        /**
         * For avoiding different element order in different DB`s
         */
        usort(
            $result,
            function (array $first, array $second) {
                return $first['id'] - $second['id'];
            }
        );

        return $result;
    }
}
