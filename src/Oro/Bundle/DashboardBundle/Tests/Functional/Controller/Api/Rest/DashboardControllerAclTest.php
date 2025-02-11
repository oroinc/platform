<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DashboardControllerAclTest extends WebTestCase
{
    /** @var Manager */
    private $dashboardManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);

        $this->dashboardManager = $this->getContainer()->get('oro_dashboard.manager');
    }

    public function testDelete()
    {
        $dashboard = $this->dashboardManager->findOneDashboardModelBy(['name' => 'main']);

        $this->assertNotNull($dashboard);

        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_dashboard',
                [
                    'id' => $dashboard->getId()
                ]
            ),
            [],
            self::generateApiAuthHeader(LoadUserData::USER_NAME)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);

        $this->assertNotNull($dashboard);
    }
}
