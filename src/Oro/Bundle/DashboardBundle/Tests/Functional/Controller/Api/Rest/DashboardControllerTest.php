<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class DashboardControllerTest extends WebTestCase
{
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
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->em     = $this->getContainer()->get('doctrine.orm.entity_manager');

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
            $this->getUrl(
                'oro_api_delete_dashboard',
                [
                    'id' => $id
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_dashboard',
                [
                    'id' => $id
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }
}
