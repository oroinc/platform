<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class ControllersTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    public function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
    }

    public function testDefault()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_calendar_view_default'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('My Calendar - John Doe', $crawler->filter('#page-title')->html());
    }

    public function testView()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_calendar_view',
                array('id' => self::DEFAULT_USER_CALENDAR_ID)
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('John Doe - Calendars - John Doe', $crawler->filter('#page-title')->html());
    }
}
