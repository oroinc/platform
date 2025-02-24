<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller;

use Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DashboardControllerAclTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadUserData::USER_NAME, LoadUserData::USER_PASSWORD)
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            array(
                'Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures\LoadUserData'
            )
        );
    }

    public function testView()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_dashboard_view')
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString(
            'Quick Launchpad',
            $result->getContent()
        );
    }
}
