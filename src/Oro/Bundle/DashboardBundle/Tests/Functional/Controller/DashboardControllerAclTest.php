<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller;

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

    protected function setUp()
    {
        $this->client = self::createClient(
            [],
            $this->generateBasicAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $this->client->appendFixturesOnce(__DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures');

        $this->dashboardManager = $this->client->getContainer()->get('oro_dashboard.manager');
    }

    public function testView()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_dashboard_view')
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(
            'Quick Launchpad',
            $result->getContent()
        );
    }
}
