<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class DashboardControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_dashboard_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_dashboard_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $createForm = $crawler->selectButton('Save and Close')->form();
        $createForm['oro_dashboard[label]'] = 'Test Dashboard';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Test Dashboard', $html);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_dashboard_update', ['id' => $this->getEntityIdFromGrid('Test Dashboard')])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $createForm = $crawler->selectButton('Save and Close')->form();
        $createForm['oro_dashboard[label]'] = 'Test Dashboard Update';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Test Dashboard Update', $html);
    }

    /**
     * @depends testUpdate
     */
    public function testView()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_dashboard_view', ['id' => $this->getEntityIdFromGrid('Test Dashboard Update')])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('Test Dashboard Update', $crawler->html());
    }

    /**
     * @param string $label
     * @return string
     */
    protected function getEntityIdFromGrid($label)
    {
        $response = $this->client->requestGrid(
            'dashboards-grid',
            [
                'dashboards-grid[_filter][label][type]' => $label,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);

        $result = reset($result['data']);

        return $result['id'];
    }
}
