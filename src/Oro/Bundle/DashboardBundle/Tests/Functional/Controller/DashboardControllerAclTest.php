<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\DashboardBundle\DataFixtures\ORM\LoadUserData;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
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
        $this->client = static::createClient(
            [],
            ToolsAPI::generateWsseHeader()
        );

        $this->dashboardManager = $this->client->getContainer()->get('oro_dashboard.manager');

        if (!self::$hasLoaded) {
            $this->client->appendFixtures(__DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures');
        }

        self::$hasLoaded = true;
    }

    public function testView()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_dashboard_view')
        );
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains(
            'Quick Launchpad',
            $result->getContent()
        );
    }
}
