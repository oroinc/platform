<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers mass delete action for recurring calendar event on its grid.
 *
 * Operations covered:
 *
 * Resources used:
 * - create event (oro_api_post_calendarevent)
 * - get events (oro_api_get_calendarevents)
 * - mass delete action oro_datagrid_mass_action
 *
 * @dbIsolation
 */
class RecurringCalendarEventMassDeleteTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);  // force load fixtures
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRecurringEventRecurrenceClearsExceptions()
    {
        // Step 1. Create new recurring event
        // Recurring event with occurrences: 2016-04-25, 2016-05-08, 2016-05-09, 2016-05-22
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-25T01:00:00+00:00',
            'end'         => '2016-04-25T02:00:00+00:00',
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ]
        ];

        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception for the recurring event, exception represents changed event
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'            => 'Test Recurring Event Changed',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-05-22T03:00:00+00:00',
                        'end'              => '2016-05-22T05:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-05-22T01:00:00+00:00',
                    ]
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create new simple calendar event
        $eventData = [
            'title'       => 'Test Simple Event',
            'description' => 'Test Simple Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-27T01:00:00+00:00',
            'end'         => '2016-04-27T02:00:00+00:00',
        ];

        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $recurringEvent */
        $simpleEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Execute delete mass action
        $url = $this->getUrl(
            'oro_datagrid_mass_action',
            [
                'gridName' => 'calendar-event-grid',
                'actionName' => 'delete',
                'inset' => 1,
                'values' => implode(',', [$simpleEvent->getId(), $changedEventException->getId()]),
            ]
        );
        $this->client->request('DELETE', $url, [], [], $this->generateBasicAuthHeader('foo_user_1', 'password'));
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true);
        $this->assertTrue($data['successful'] === true);
        $this->assertTrue($data['count'] === 2);

        // Step 5. Get events via API and verify result is without removed items
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'       => '2016-04-01T01:00:00+00:00',
                        'end'         => '2016-06-01T01:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode' => 200,
                'contentType' => 'application/json'
            ]
        );

        $expectedResponse = [
            [
                'id' => $recurringEvent->getId(),
                'title' => "Test Recurring Event",
                'description' => "Test Recurring Event Description",
                'start' => "2016-04-25T01:00:00+00:00",
                'end' => "2016-04-25T02:00:00+00:00",
                'allDay' => false,
                'attendees' => [],
            ],
            [
                'id' => $recurringEvent->getId(),
                'title' => "Test Recurring Event",
                'description' => "Test Recurring Event Description",
                'start' => "2016-05-08T01:00:00+00:00",
                'end' => "2016-05-08T02:00:00+00:00",
                'allDay' => false,
                'attendees' => [],
            ],
            [
                'id' => $recurringEvent->getId(),
                'title' => "Test Recurring Event",
                'description' => "Test Recurring Event Description",
                'start' => "2016-05-09T01:00:00+00:00",
                'end' => "2016-05-09T02:00:00+00:00",
                'allDay' => false,
                'attendees' => [],
            ]
        ];

        $actualIntersect = self::getRecursiveArrayIntersect($response, $expectedResponse);
        \PHPUnit_Framework_TestCase::assertEquals(
            $expectedResponse,
            $actualIntersect,
            null
        );

        // Step 6. Get exception event via API and verify it is cancelled
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl('oro_api_get_calendarevent', ['id' => $changedEventException->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode' => 200,
                'contentType' => 'application/json'
            ]
        );

        $expectedResponse = [
            'id' => $changedEventException->getId(),
            'title' => "Test Recurring Event Changed",
            'description' => "Test Recurring Event Description",
            'start' => "2016-05-22T03:00:00+00:00",
            'end' => "2016-05-22T05:00:00+00:00",
            'allDay' => false,
            'attendees' => [],
            'recurringEventId' => $recurringEvent->getId(),
            'isCancelled' => true,
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Get intersect of $target array with values of keys in $source array. If key is an array in both places then
     * the value of this key will be returned as intersection as well.
     *
     * @param array $source
     * @param array $target
     * @return array
     */
    public static function getRecursiveArrayIntersect(array $target, array $source)
    {
        $result = [];
        foreach (array_keys($source) as $key) {
            if (array_key_exists($key, $target)) {
                if (is_array($target[$key]) && is_array($source[$key])) {
                    $result[$key] = self::getRecursiveArrayIntersect($target[$key], $source[$key]);
                } else {
                    $result[$key] = $target[$key];
                }
            }
        }

        return $result;
    }
}
