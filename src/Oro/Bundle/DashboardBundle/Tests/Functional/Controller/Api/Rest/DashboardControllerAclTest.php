<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class DashboardControllerAclTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Manager
     */
    protected $dashboardManager;

    /**
     * @var bool
     */
    protected static $hasLoaded = false;

    protected function setUp()
    {
        $this->client = self::createClient(
            [],
            $this->generateWsseAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );

        $this->dashboardManager = $this->client->getContainer()->get('oro_dashboard.manager');

        if (!self::$hasLoaded) {
            $this->client->appendFixtures(
                __DIR__ . implode('..', array_fill(0, 3, DIRECTORY_SEPARATOR)) . 'DataFixtures'
            );
        }

        self::$hasLoaded = true;
    }

    public function testDelete()
    {
        $dashboard = $this
            ->dashboardManager
            ->findOneDashboardModelBy(['name' => 'main']);

        $this->assertNotNull($dashboard);

        $this->client->request(
            'DELETE',
            $this->client->generate(
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
