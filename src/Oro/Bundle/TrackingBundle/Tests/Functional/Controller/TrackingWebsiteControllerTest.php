<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @group segfault
 */
class TrackingWebsiteControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testCreate()
    {
        $crawler                                  = $this->client->request(
            'GET',
            $this->getUrl('oro_tracking_website_create')
        );

        $form                                     = $crawler->selectButton('Save and Close')->form();
        $form['oro_tracking_website[name]']       = 'name';
        $form['oro_tracking_website[identifier]'] = 'unique';
        $form['oro_tracking_website[url]']        = 'http://example.com';
        $form['oro_tracking_website[owner]']      = '1';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Tracking Website saved", $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testView()
    {
        $this->markTestSkipped('skipping because of Segmentation fault in travis. Should be fixed in CRM-5973');
        $response = $this->client->requestGrid(
            'website-grid'
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->client->request(
            'GET',
            $this->getUrl('oro_tracking_website_view', ['id' => $result['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @depends testCreate
     *
     * @return int
     */
    public function testGetTrackIdByIdentifier()
    {
        $this->markTestSkipped('skipping because of Segmentation fault in travis. Should be fixed in CRM-5973');
        $response = $this->client->requestGrid(
            'website-grid',
            ['website-grid[_filter][identifier][value]' => 'unique']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        return $result['id'];
    }

    /**
     * @depends testGetTrackIdByIdentifier
     *
     * @param int $id
     */
    public function testUpdate($id)
    {
        $this->markTestSkipped('skipping because of Segmentation fault in travis. Should be fixed in CRM-5973');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tracking_website_update', ['id' => $id])
        );

        $form                                     = $crawler->selectButton('Save and Close')->form();
        $form['oro_tracking_website[name]']       = 'nameUP';
        $form['oro_tracking_website[identifier]'] = 'unique2';
        $form['oro_tracking_website[url]']        = 'http://example.org';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Tracking Website saved", $crawler->html());
    }

    /**
     * @depends testUpdate
     */
    public function testIndex()
    {
        $this->markTestSkipped('skipping because of Segmentation fault in travis. Should be fixed in CRM-5973');
        $this->client->request('GET', $this->getUrl('oro_tracking_website_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('unique2', $result->getContent());
    }
}
