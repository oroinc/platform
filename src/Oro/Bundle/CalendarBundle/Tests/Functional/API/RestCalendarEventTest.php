<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestCalendarEventTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    /** @var Client  */
    protected $client;

    public function setUp()
    {
        $this->client = self::createClient(array(), $this->generateWsseAuthHeader());
    }

    public function testGets()
    {
        $request = array(
            "calendar" => self::DEFAULT_USER_CALENDAR_ID,
            "start" => date(DATE_RFC3339, strtotime('-1 day')),
            "end" => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => false
        );

        $this->client->request('GET', $this->client->generate('oro_api_get_calendarevents', $request));

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
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'id'       => null,
            'title'    => "Test Event",
            'start'    => date(DATE_RFC3339),
            'end'      => date(DATE_RFC3339),
            'allDay'   => true
        );
        $this->client->request('POST', $this->client->generate('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPost
     * @param int $id
     */
    public function testPut($id)
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'title'    => 'Test Event Updated',
            'start'    => date(DATE_RFC3339),
            'end'      => date(DATE_RFC3339),
            'allDay'   => true
        );
        $this->client->request(
            'PUT',
            $this->client->generate('oro_api_put_calendarevent', array("id" => $id)),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 204);

        $this->assertEmpty($result);

        return $id;
    }

    /**
     * @depends testPut
     * @param int $id
     */
    public function testGet($id)
    {
        $request = array(
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => date(DATE_RFC3339, strtotime('-1 day')),
            'end'         => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => true
        );
        $this->client->request('GET', $this->client->generate('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result[0]['id']);
    }
}
