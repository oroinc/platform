<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestCalendarEventTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    public function testGets()
    {
        $request = array(
            "calendar" => self::DEFAULT_USER_CALENDAR_ID,
            "start" => date(DATE_RFC3339, strtotime('-1 day')),
            "end" => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => false
        );

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEmpty($result);
    }

    /**
     * Create new event
     *
     * @return int
     */
    public function testPost()
    {
        $request = array(
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => "Test Event",
            'description'     => "Test Description",
            'start'           => date(DATE_RFC3339),
            'end'             => date(DATE_RFC3339),
            'allDay'          => true,
            'backgroundColor' => '#FF0000'
        );
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPost
     *
     * @param int $id
     *
     * @return int
     */
    public function testPut($id)
    {
        $request = array(
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'title'           => 'Test Event Updated',
            'description'     => 'Test Description Updated',
            'start'           => date(DATE_RFC3339),
            'end'             => date(DATE_RFC3339),
            'allDay'          => true,
            'backgroundColor' => '#FF0000'
        );
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', array("id" => $id)),
            $request
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        return $id;
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testGet($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testGetByCalendar($id)
    {
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

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testCget($id)
    {
        $request = array(
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => date(DATE_RFC3339, strtotime('-1 day')),
            'end'         => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => true
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result[0]['id']);
    }

    /**
     * @depends testPut
     */
    public function testCgetFiltering()
    {
        $request = array(
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'page'        => 1,
            'limit'       => 10,
            'subordinate' => false
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request) . '&createdAt>2014-03-04T20:00:00+0000'
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request) . '&createdAt>2050-03-04T20:00:00+0000'
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($result);
    }
}
