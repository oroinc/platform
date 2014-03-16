<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class RestCalendarEventTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    /** @var Client  */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
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
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());

        $this->assertEmpty($result);
    }

    /**
     * create new event
     * @return int
     */
    public function testPost()
    {
        $request = array(
            "calendar" => self::DEFAULT_USER_CALENDAR_ID,
            "id" => null,
            "title" => "Test Event",
            "start" => date(DATE_RFC3339),
            "end" => date(DATE_RFC3339),
            "allDay" => true
        );
        $this->client->request('POST', $this->client->generate('oro_api_post_calendarevent'), $request);
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result);
        $result = ToolsAPI::jsonToArray($result->getContent());

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPost
     *
     * @param $id
     */
    public function testPut($id)
    {
        $request = array(
            "calendar" => self::DEFAULT_USER_CALENDAR_ID,
            "title" => "Test Event Updated",
            "start" => date(DATE_RFC3339),
            "end" => date(DATE_RFC3339),
            "allDay" => true
        );
        $this->client->request(
            'PUT',
            $this->client->generate('oro_api_put_calendarevent', array("id" => $id)),
            $request
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);
        $result = ToolsAPI::jsonToArray($result->getContent());

        $this->assertEmpty($result);
    }

    /**
     * @depends testPost
     * @depends testPut
     */
    public function testGet($id)
    {
        $request = array(
            "calendar" => self::DEFAULT_USER_CALENDAR_ID,
            "start" => date(DATE_RFC3339, strtotime('-1 day')),
            "end" => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => true
        );
        $this->client->request('GET', $this->client->generate('oro_api_get_calendarevents', $request));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());

        $this->assertNotEmpty($result);
    }
}
