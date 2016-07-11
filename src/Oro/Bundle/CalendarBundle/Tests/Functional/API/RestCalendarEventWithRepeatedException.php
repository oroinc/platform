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
class RestCalendarEventWithRepeatedException extends WebTestCase
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

    /**
     * Check pre conditions
     */
    public function testGets()
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
     * Creates recurring event for splitting.
     *
     * @depends testGets
     *
     * @return array
     */
    public function testPostRecurringEvent()
    {
        $request = [
            'title'       => 'Test Recurring Event splitting',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => gmdate(DATE_RFC3339),
            'end'         => gmdate(DATE_RFC3339),
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

        return ['id' => $result['id'], 'recurrenceId' => $event->getRecurrence()->getId()];
    }

    /**
     * @depends testPostRecurringEvent
     *
     * @param array $param
     */
    public function testGetRecurringEvent(array $param)
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevent', ['id' => $param['id']]));
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        $recurrence = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:Recurrence')
            ->find($param['recurrenceId']);

        $this->assertNotEmpty($recurrence);
        $this->assertNotNull($recurrence->getId());
    }

    /**
     * @depends testPostRecurringEvent
     *
     * @param array $param
     */
    public function testMakeRecurringEventWithException(array $param)
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
            'recurringEventId' => $param['id'],
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
            ->findAll();

        $this->assertCount(2, $events);

        $result = array_values(
            array_filter(
                $events,
                function ($event) use ($response) {
                    return $event->getId() === $response['id'];
                }
            )
        );

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]->isCancelled());

        $param['excluded'] = $result[0]->getId();

        return $param;
    }

    /**
     * @depends  testMakeRecurringEventWithException
     *
     * @param array $param
     *
     * @return array
     */
    public function testDeleteRecurringEventWithException(array $param)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $param['excluded']])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 204);
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        $event = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($param['excluded']);

        $this->assertNotNull($event);
        $this->assertTrue($event->isCancelled());

        return $param;
    }

    /**
     * Creates recurring event for splitting.
     *
     * @depends testDeleteRecurringEventWithException
     *
     * @param array $param
     *
     * @return array
     */
    public function testUpdateRecurringEvent(array $param)
    {
        $request = [
            'title'       => 'Test Recurring Event splitting',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => gmdate(DATE_RFC3339),
            'end'         => gmdate(DATE_RFC3339),
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

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $param['id']]),
            $request
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);

        /** @var CalendarEvent $event */
        $event = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($param['id']);
        $this->assertNotNull($event);

        $eventExclusion = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($param['excluded']);
        $this->assertNotNull($eventExclusion);

        return $param;
    }
}
