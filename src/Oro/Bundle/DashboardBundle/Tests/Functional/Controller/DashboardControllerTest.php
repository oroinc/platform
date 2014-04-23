<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 */
class DashboardControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
    }

    public function testIndex()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_dashboard_index'),
            [],
            [],
            ToolsAPI::generateWsseHeader()
        );
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
            $this->client->generate('oro_dashboard_view'),
            [],
            [],
            ToolsAPI::generateWsseHeader()
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains(
            'Dashboard',
            $result->getContent()
        );
    }
}
