<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DashboardControllerAclTest extends WebTestCase
{
    /**
     * @var Manager
     */
    protected $dashboardManager;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures\LoadUserData']);

        $this->dashboardManager = $this->getContainer()->get('oro_dashboard.manager');
    }

    public function testDelete()
    {
        $dashboard = $this->dashboardManager->findOneDashboardModelBy(['name' => 'main']);

        $this->assertNotNull($dashboard);

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_dashboard',
                [
                    'id' => $dashboard->getId()
                ]
            ),
            [],
            [],
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);

        $this->assertNotNull($dashboard);
    }
}
