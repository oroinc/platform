<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    /** @var Dashboard */
    private $dashboard;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->dashboard = new Dashboard();
        $this->dashboard->setName('dashboard');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($this->dashboard);
        $em->flush();
    }

    public function testDelete()
    {
        $id = $this->dashboard->getId();

        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_dashboard', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_dashboard', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }
}
