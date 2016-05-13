<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RestCalendarEventTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    /** @var array */
    protected static $regularEventParameters;

    /** @var array */
    protected static $recurringEventParameters;

    /** @var array */
    protected static $recurringEventExceptionParameters;

    public static function setUpBeforeClass()
    {
        self::$regularEventParameters = [
            'title' => 'Test Regular Event',
            'description' => 'Test Regular Event Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
        ];
        self::$recurringEventParameters = [
            'title' => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
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
            ],
        ];
        self::$recurringEventExceptionParameters = [
            'title' => 'Test Recurring Event Exception',
            'description' => 'Test Recurring Exception Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'recurringEventId' => -1, // is set dynamically
            'originalStart' => gmdate(DATE_RFC3339),
            'isCancelled' => true,
        ];
    }

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData']);
    }

    /**
     * Creates regular event.
     *
     * @return int
     */
    public function testPostRegularEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$regularEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);

        return $result['id'];
    }

    /**
     * Reads regular event.
     *
     * @depends testPostRegularEvent
     *
     * @param int $id
     */
    public function testGetRegularEvent($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
        foreach (self::$regularEventParameters as $attribute => $value) {
            $this->assertArrayHasKey($attribute, $result);
            $this->assertEquals(
                $value,
                $result[$attribute],
                sprintf('Failed assertion for $result["%s"] value: ', $attribute)
            );
        }
    }

    /**
     * Updates regular event.
     *
     * @depends testPostRegularEvent
     *
     * @param int $id
     */
    public function testPutRegularEvent($id)
    {
        self::$regularEventParameters['title'] = 'Test Regular Event Updated';
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            self::$regularEventParameters
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);
        $this->assertEquals(self::$regularEventParameters['title'], $event->getTitle());
    }

    /**
     * Deletes regular event.
     *
     * @depends testPostRegularEvent
     *
     * @param int $id
     */
    public function testDeleteRegularEvent($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $id])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $id]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
    }

    /**
     * Creates recurring event.
     *
     * @return array
     */
    public function testPostRecurringEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);
        $this->assertEquals(Recurrence::MAX_END_DATE, $event->getRecurrence()->getEndTime()->format(DATE_RFC3339));

        return ['id' => $result['id'], 'recurrenceId' => $event->getRecurrence()->getId()];
    }

    /**
     * Reads recurring event.
     *
     * @depends testPostRecurringEvent
     *
     * @param array $data
     */
    public function testGetRecurringEvent($data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['recurrenceId'], $result['recurrence']['id']);
    }

    /**
     * Updates recurring event. The goal is to test transformation from recurring event to regular one.
     * Dependency for testPostRecurringEvent is not injected to work with own new recurring event.
     */
    public function testPutRecurringEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $data['id'] = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();
        $recurringEventParameters = self::$recurringEventParameters;
        $recurringEventParameters['recurrence'] = null; // recurring event will become regular event.
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventParameters
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]);
        $this->assertNull($event->getRecurrence());
        $recurrence = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:Recurrence')
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($event);
        $em->flush();
    }

    /**
     * Deletes recurring event.
     * Dependency for testPostRecurringEvent is not injected to work with own new recurring event.
     *
     * @TODO add test when recurring event with exception is deleted.
     */
    public function testDeleteRecurringEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $data['id'] = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $recurrence = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:Recurrence')
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);
    }

    /**
     * Creates recurring event exception.
     *
     * @depends testPostRecurringEvent
     *
     * @param array $data
     *
     * @return array
     */
    public function testPostRecurringEventException($data)
    {
        self::$recurringEventExceptionParameters['recurringEventId'] = $data['id'];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            self::$recurringEventExceptionParameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $exception = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($exception);
        $this->assertEquals($data['id'], $exception->getRecurringEvent()->getId());

        return ['id' => $result['id'], 'recurringEventId' => $data['id']];
    }

    /**
     * Reads recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testGetRecurringEventException($data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        foreach (self::$recurringEventExceptionParameters as $attribute => $value) {
            $this->assertArrayHasKey($attribute, $result);
            $this->assertEquals(
                $value,
                $result[$attribute],
                sprintf('Failed assertion for $result["%s"] value: ', $attribute)
            );
        }
    }

    /**
     * Updates recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testPutRecurringEventException($data)
    {
        self::$recurringEventExceptionParameters['isCancelled'] = false;
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            self::$recurringEventExceptionParameters
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($data['id']);
        $this->assertEquals(self::$recurringEventExceptionParameters['isCancelled'], $event->getIsCancelled());
    }

    /**
     * Deletes recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testDeleteRecurringEventException($data)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $registry = $this->getContainer()->get('doctrine');
        $recurringEvent = $registry->getRepository('OroCalendarBundle:CalendarEvent')
            ->find(['id' => $data['recurringEventId']]);
        $registry->getManager()->remove($recurringEvent);
        $registry->getManager()->flush();
    }

    public function testCgetByDateRangeFilter()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'start' => gmdate(DATE_RFC3339, strtotime('-5 day')),
            'end' => gmdate(DATE_RFC3339, strtotime('+5 day')),
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(5, $result);
    }

    public function testCgetByPagination()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'page' => 1,
            'limit' => 100,
            'subordinate' => false
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(8, $result);
    }

    public function testCgetByPaginationWithRecurringEventIdFilter()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'page' => 1,
            'limit' => 100,
            'subordinate' => false,
            'recurringEventId' => $this->getReference('eventInRangeWithCancelledException')->getId(),
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result);
    }

    public function testGetByCalendar()
    {
        $id = $this->getReference('eventInRangeWithCancelledException')->getId();
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_calendarevent_by_calendar',
                ['id' => self::DEFAULT_USER_CALENDAR_ID, 'eventId' => $id]
            )
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
    }
}
