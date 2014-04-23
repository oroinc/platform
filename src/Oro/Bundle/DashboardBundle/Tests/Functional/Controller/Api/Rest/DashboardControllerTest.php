<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;
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

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Dashboard
     */
    protected $dashboard;

    protected function setUp()
    {
        $this->client = static::createClient([], ToolsAPI::generateWsseHeader());
        $this->em     = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $this->dashboard = new Dashboard();
        $this->dashboard->setName('dashboard');

        $this->em->persist($this->dashboard);
        $this->em->flush();
    }


    public function testDelete()
    {
        $id = $this->dashboard->getId();

        $this->client->request(
            'DELETE',
            $this->client->generate(
                'oro_api_delete_dashboard',
                [
                    'id' => $id
                ]
            ),
            [],
            [],
            ToolsAPI::generateWsseHeader()
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $this->client->request(
            'DELETE',
            $this->client->generate(
                'oro_api_delete_dashboard',
                [
                    'id' => $id
                ]
            ),
            [],
            [],
            ToolsAPI::generateWsseHeader()
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 404);
    }
}
