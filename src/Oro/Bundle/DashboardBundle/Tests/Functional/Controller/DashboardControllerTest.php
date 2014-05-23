<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 */
class DashboardControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_dashboard_index')
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Dashboard', $result->getContent());
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
            'Dashboard',
            $result->getContent()
        );
    }
}
