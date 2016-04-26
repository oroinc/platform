<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class DashboardControllerAclTest extends WebTestCase
{
    protected function setUp()
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
        $this->assertContains(
            'Quick Launchpad',
            $result->getContent()
        );
    }
}
