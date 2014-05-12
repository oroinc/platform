<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class ControllersGroupTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateBasicHeader());
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->client->generate('oro_user_group_index'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
    }

    public function testCreate()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $this->client->generate('oro_user_group_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_group_form[name]'] = 'testGroup';
        $form['oro_user_group_form[owner]']= 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Group saved", $crawler->html());
    }

    public function testUpdate()
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'groups-grid',
            array(
                'groups-grid[_filter][name][value]' => 'testGroup'
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        /** @var Crawler $crawler */
        $crawler = $this->client->request(
            'GET',
            $this->client->generate('oro_user_group_update', array('id' => $result['id']))
        );
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_group_form[name]'] = 'testGroupUpdated';
        $form['oro_user_group_form[appendUsers]'] = 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Group saved", $crawler->html());
    }

    public function testGridData()
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'groups-grid',
            array(
                'groups-grid[_filter][name][value]' => 'testGroupUpdated'
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);

        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'group-users-grid',
            array(
                'group-users-grid[_filter][has_group][value]' => 1,
                'group-users-grid[group_id]' => $result['id']
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        $this->assertEquals(1, $result['id']);
    }
}
