<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ControllersGroupTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_user_group_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $this->getUrl('oro_user_group_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_group_form[name]'] = 'testGroup';
        $form['oro_user_group_form[owner]']= 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Group saved", $crawler->html());
    }

    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'groups-grid',
            array('groups-grid[_filter][name][value]' => 'testGroup')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_group_update', array('id' => $result['id']))
        );
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_group_form[name]'] = 'testGroupUpdated';
        $form['oro_user_group_form[appendUsers]'] = 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Group saved", $crawler->html());
    }

    public function testGridData()
    {
        $response = $this->client->requestGrid(
            'groups-grid',
            array('groups-grid[_filter][name][value]' => 'testGroupUpdated')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $response = $this->client->requestGrid(
            'group-users-grid',
            array(
                'group-users-grid[_filter][has_group][value]' => 1,
                'group-users-grid[group_id]' => $result['id']
            )
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertEquals(1, $result['id']);
    }
}
