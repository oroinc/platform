<?php

namespace OroCRM\Bundle\IntegrationBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DomCrawler\Form;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class ChannelControllersTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(
            array(),
            array_merge(ToolsAPI::generateBasicHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->client->generate('oro_integration_channel_index'));
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains('Channels - System', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->client->generate('oro_integration_channel_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $name = 'name' . ToolsAPI::generateRandomString();
        $form['oro_integration_channel_form[name]'] = 'Simple channel';
        $form['oro_integration_channel_form[type]'] = 'simple';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Channel saved", $crawler->html());

        return $name;
    }

    /**
     * @param $name
     * @depends testCreate
     *
     * @return array
     */
    public function testUpdate($name)
    {
        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'channels-grid',
            array(
                'channels[_filter][name][value]' => $name,
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);

        $result = ToolsAPI::jsonToArray($result->getContent());
        $result = reset($result['data']);
        $channel = $result;
        $crawler = $this->client->request(
            'GET',
            $this->client->generate('oro_integration_channel_update', array('id' => $result['id']))
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $name = 'name' . ToolsAPI::generateRandomString();
        $form['oro_integration_channel_form[name]'] = $name;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Channel saved", $crawler->html());

        $channel['name'] = $name;
        return $channel;
    }

    /**
     * @param $channel
     * @depends testUpdate
     *
     * @return string
     */
    public function testSchedule($channel)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_integration_channel_schedule', array('id' => $channel['id']))
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result['job_id']);
    }

    /**
     * @param $channel
     * @depends testUpdate
     */
    public function testDelete($channel)
    {
        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_channel', array('id' => $channel['id']))
        );

        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 204);

        $result = ToolsAPI::getEntityGrid(
            $this->client,
            'channels-grid',
            array(
                'channels[_filter][name][value]' => $channel['name'],
            )
        );

        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $this->assertEmpty($result['data']);
        $this->assertEmpty($result['options']['totalRecords']);
    }
}
