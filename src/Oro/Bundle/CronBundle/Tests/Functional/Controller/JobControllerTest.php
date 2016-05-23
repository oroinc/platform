<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class JobControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $this->client->useHashNavigation(true);
        $this->client->request('GET', $this->getUrl('oro_cron_job_index'));
        $this->client->useHashNavigation(false);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testRunDaemon()
    {
        $this->client->followRedirects(true);
        $this->client->request('GET', $this->getUrl('oro_cron_job_run_daemon'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testRunDaemon
     */
    public function testGetStatus()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_cron_job_status'),
            array(),
            array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertGreaterThan(0, (int)$result->getContent());
    }

    /**
     * @depends testRunDaemon
     */
    public function testStopDaemon()
    {
        $this->client->request('GET', $this->getUrl('oro_cron_job_stop_daemon'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->client->request(
            'GET',
            $this->getUrl('oro_cron_job_status'),
            array(),
            array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals(0, (int)$result->getContent());
    }
}
