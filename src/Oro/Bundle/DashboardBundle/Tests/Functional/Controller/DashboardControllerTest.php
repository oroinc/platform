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
        $this->assertContains('main', $result->getContent());
    }

    public function testView()
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'dashboards-grid',
            array(
                'dashboards-grid[_filter][name][value]' => 'main'
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->client->generate('oro_dashboard_view', array('id' => $result['id']))
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains('main', $crawler->html());
        $this->assertContains('John Doe', $crawler->html());
    }

    /**
     * simple test
     */
    public function testOpen()
    {
        $dashboards = $this->client->getKernel()->getContainer()->get('oro_dashboard.manager')->getDashboards();
        foreach ($dashboards as $dashboard) {
            $this->client->request(
                'GET',
                $this->client->generate(
                    'oro_dashboard_open',
                    array('id' => $dashboard->getDashboard()->getId())
                )
            );
            $result = $this->client->getResponse();
            ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
            $this->assertContains(
                $this->client->getKernel()
                    ->getContainer()
                    ->get('translator')
                    ->trans(
                        $dashboard->getDashboard()
                            ->getName()
                    ),
                $result->getContent()
            );
        }
    }
}
