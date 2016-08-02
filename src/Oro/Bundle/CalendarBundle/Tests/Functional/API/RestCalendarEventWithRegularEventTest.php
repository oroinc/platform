<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestCalendarEventWithRegularEventTest extends AbstractCalendarEventTest
{
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

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertFalse($result['notifiable']);
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


    public function testCgetByDateRangeFilter()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'start' => gmdate(DATE_RFC3339, strtotime(self::DATE_RANGE_START)),
            'end' => gmdate(DATE_RFC3339, strtotime(self::DATE_RANGE_END)),
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(16, $result);
    }

    public function testCgetByDateRangeFilterWithSummerWinterTimeChecking()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'start' => date_create('2016-01-21 00:00:00', new \DateTimeZone('UTC'))->format(DATE_RFC3339),
            'end' => date_create('2016-01-23 00:00:00', new \DateTimeZone('UTC'))->format(DATE_RFC3339),
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result);
        $this->assertEquals($result[0]['start'], '2016-01-21T04:00:00+00:00');
        $this->assertEquals($result[0]['end'], '2016-01-21T05:00:00+00:00');

        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'start' => date_create('2016-06-21 00:00:00', new \DateTimeZone('UTC'))->format(DATE_RFC3339),
            'end' => date_create('2016-06-23 00:00:00', new \DateTimeZone('UTC'))->format(DATE_RFC3339),
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result);
        $this->assertEquals($result[0]['start'], '2016-06-21T03:00:00+00:00');
        $this->assertEquals($result[0]['end'], '2016-06-21T04:00:00+00:00');
    }

    public function testCgetByPagination()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'page' => 1,
            'limit' => 10,
            'subordinate' => false
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(10, $result);
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
