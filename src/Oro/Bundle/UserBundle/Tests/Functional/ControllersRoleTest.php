<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @outputBuffering enabled
 * @db_isolation
 * @db_reindex
 */
class ControllersRoleTest extends WebTestCase
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
        $this->client->request('GET', $this->client->generate('oro_user_role_index'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
    }

    public function testCreate()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $this->client->generate('oro_user_role_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_role_form[label]'] = 'testRole';
        $form['oro_user_role_form[owner]']= 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Role saved", $crawler->html());
    }

    public function testUpdate()
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'roles-grid',
            array(
                'roles-grid[_filter][label][value]' => 'testRole'
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request(
            'GET',
            $this->client->generate('oro_user_role_update', array('id' => $result['id']))
        );
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_role_form[label]'] = 'testRoleUpdated';
        $form['oro_user_role_form[appendUsers]'] = 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Role saved", $crawler->html());
    }

    public function testGridData()
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'roles-grid',
            array(
                'roles-grid[_filter][label][value]' => 'testRoleUpdated'
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);

        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'role-users-grid',
            array(
                'role-users-grid[_filter][has_role][value]' => 1,
                'role-users-grid[role_id]' => $result['id']
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        $this->assertEquals(1, $result['id']);
    }
}
