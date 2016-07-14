<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RestCalendarEventWithCancelledAndUpdatedRecurrentEventTest extends WebTestCase
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

    public function testCalendarEventWithCancelledAndUpdatedRecurrentEventTest()
    {
        $result = $this->getAllEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertEmpty($result);

        $recurringEventData = $this->getRecurringEventData();
        $recurringEvent = $this->postCalendarEventRequest($recurringEventData);
        $this->postCancelledCalendarEvent($recurringEvent);

        $this->verifyCreatedData($recurringEvent);

        $recurringEventData['start'] = '2016-07-02T11:00:00Z';
        $recurringEventData['end'] = '2016-07-02T12:00:00Z';
        $this->putCalendarEventRequest($recurringEvent->getId(), $recurringEventData);

        $exceptions = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['cancelled' => true, 'recurringEvent' => $recurringEvent->getId()]);
        $this->assertCount(1, $exceptions);
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $exceptions[0]->getId()])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        $this->verifyUpdatedData($recurringEvent);
    }

    /**
     * @param array $calendarEvent
     *
     * @return CalendarEvent
     */
    protected function postCalendarEventRequest(array $calendarEvent)
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $calendarEvent);
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
     * @param integer $calendarEventId
     * @param array $calendarEvent
     */
    protected function putCalendarEventRequest($calendarEventId, array $calendarEvent)
    {
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $calendarEventId]),
            $calendarEvent
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
    }

    /**
     * @param CalendarEvent $recurringCalendarEvent
     *
     * @return CalendarEvent
     */
    protected function postCancelledCalendarEvent(CalendarEvent $recurringCalendarEvent)
    {
        $cancelledEvent = [
            'title'            => $recurringCalendarEvent->getTitle(),
            'description'      => $recurringCalendarEvent->getDescription(),
            'originalStart'    => '2016-07-09T09:00:00Z',
            'isCancelled'      => true,
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'recurringEventId' => $recurringCalendarEvent->getId(),
            'start'            => '2016-07-09T11:00:00Z',
            'end'              => '2016-07-09T12:00:00Z',
        ];

        return $this->postCalendarEventRequest($cancelledEvent);
    }

    /**
     * @param integer $calendarId
     *
     * @return array
     */
    protected function getAllEvents($calendarId)
    {
        $request = [
            'calendar'    => $calendarId,
            'start'       => '2016-07-01T00:00:01Z',
            'end'         => '2016-07-31T23:59:59Z',
            'subordinate' => true,
        ];
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    /**
     * @param CalendarEvent $recurringEvent
     */
    protected function verifyCreatedData(CalendarEvent $recurringEvent)
    {
        $result = $this->getAllEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCount(4, $result);

        $event = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['id' => $recurringEvent->getId()]);
        $this->assertCount(1, $event);

        $childEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['parent' => $recurringEvent->getId()]);
        $this->assertCount(1, $childEvent);

        $exceptions = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['cancelled' => true, 'recurringEvent' => [$event[0]->getId(), $childEvent[0]->getId()]]);
        $this->assertCount(2, $exceptions);

        $eventData = [
            'title' => $event[0]->getTitle(),
            'description' => $event[0]->getDescription(),
            'start' => $event[0]->getStart(),
            'end' => $event[0]->getEnd(),
            'allDay' => $event[0]->getAllDay(),
        ];

        $childEventData = [
            'title' => $childEvent[0]->getTitle(),
            'description' => $childEvent[0]->getDescription(),
            'start' => $childEvent[0]->getStart(),
            'end' => $childEvent[0]->getEnd(),
            'allDay' => $childEvent[0]->getAllDay(),
        ];

        $this->assertEquals($eventData, $childEventData);

        $exception1 = [
            'title' => $exceptions[0]->getTitle(),
            'description' => $exceptions[0]->getDescription(),
            'start' => $exceptions[0]->getStart(),
            'end' => $exceptions[0]->getEnd(),
            'allDay' => $exceptions[0]->getAllDay(),
        ];

        $exception2 = [
            'title' => $exceptions[1]->getTitle(),
            'description' => $exceptions[1]->getDescription(),
            'start' => $exceptions[1]->getStart(),
            'end' => $exceptions[1]->getEnd(),
            'allDay' => $exceptions[1]->getAllDay(),
        ];

        $this->assertEquals($exception1, $exception2);
    }

    /**
     * @param CalendarEvent $recurringEvent
     */
    protected function verifyUpdatedData(CalendarEvent $recurringEvent)
    {
        $events = $this->getAllEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCount(5, $events);

        foreach ($events as $event) {
            $start = new \DateTime($event['start']);
            $end = new \DateTime($event['end']);
            $this->assertEquals($start->format('H:i:s'), '11:00:00');
            $this->assertEquals($end->format('H:i:s'), '12:00:00');
        }

        $childEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['parent' => $recurringEvent->getId()]);
        $this->assertCount(1, $childEvent);

        $events = $this->getAllEvents($childEvent[0]->getCalendar()->getId());
        $this->assertCount(5, $events);

        $exceptions = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findBy(['recurringEvent' => [$recurringEvent->getId(), $childEvent[0]->getId()]]);
        $this->assertCount(0, $exceptions);
    }

    /**
     * @return array
     */
    protected function getRecurringEventData()
    {
        $user = $this->getReference('simple_user');

        return [
            'title' => 'Test Weekly Recurring Event',
            'description' => 'Test Weekly Recurring Event Description',
            'start' => '2016-07-02T09:00:00Z',
            'end' => '2016-07-02T09:30:00Z',
            'allDay' => false,
            'backgroundColor' => '#FF0000',
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => ['saturday'],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => '2016-07-02T09:00:00Z',
                'endTime' => null,
                'occurrences' => 5,
                'timeZone' => 'UTC'
            ],
            'attendees' => [
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => 'Test Testov',
                    'email'       => 'test@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
            ],
        ];
    }
}
