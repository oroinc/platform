<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
 */
class ControllersTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateBasicHeader());
    }

    public function testDefault()
    {
        $crawler = $this->client->request('GET', $this->client->generate('oro_calendar_view_default'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertEquals('My Calendar - John Doe', $crawler->filter('title')->html());
    }

    public function testView()
    {
        $crawler = $this->client->request(
            'GET',
            $this->client->generate(
                'oro_calendar_view',
                array('id' => self::DEFAULT_USER_CALENDAR_ID)
            )
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertEquals('John Doe - Calendars - John Doe', $crawler->filter('#page-title')->html());
    }
}
