<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DomCrawler\Form;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
 */
class ControllersTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateBasicHeader());
    }

    /**
     * simple test
     */
    public function testIndex()
    {
        $dashboards = $this->client->getKernel()->getContainer()->get('oro_dashboard.manager')->getDashboards();
        foreach ($dashboards as $dashboardName => $dashboard) {
            $this->client->request(
                'GET',
                $this->client->generate(
                    'oro_dashboard_index',
                    array('name' => $dashboardName)
                )
            );
            $result = $this->client->getResponse();
            ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
            $this->assertContains(
                $this->client->getKernel()->getContainer()->get('translator')->trans($dashboard),
                $result->getContent()
            );
        }
    }
}
