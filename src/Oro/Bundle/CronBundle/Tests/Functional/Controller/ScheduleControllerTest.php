<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ScheduleControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cron_schedule_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('oro-cron-schedules-grid', $crawler->html());
    }
}
