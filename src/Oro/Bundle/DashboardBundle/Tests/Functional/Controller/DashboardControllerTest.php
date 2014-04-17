<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
 */
class DashboardControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateBasicHeader());
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->client->generate('oro_dashboard_index'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains('Dashboard', $result->getContent());
    }

    /**
     * simple test
     */
    public function testView()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_dashboard_view')
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains(
            'Dashboard',
            $result->getContent()
        );
    }
}
