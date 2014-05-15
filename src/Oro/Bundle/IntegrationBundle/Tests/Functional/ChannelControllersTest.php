<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ChannelControllersTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_channel_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Channels - System', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_channel_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $name = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]'] = 'Simple channel';
        $form['oro_integration_channel_form[type]'] = 'simple';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
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
        $response = $this->client->requestGrid(
            'channels-grid',
            array('channels[_filter][name][value]' => $name)
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $channel = $result;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_integration_channel_update', array('id' => $result['id']))
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $name = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]'] = $name;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200, 'text/html; charset=UTF-8');
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
            $this->getUrl('oro_integration_channel_schedule', array('id' => $channel['id']))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

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
            $this->getUrl('oro_api_delete_channel', array('id' => $channel['id']))
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 204);

        $response = $this->client->requestGrid(
            'channels-grid',
            array('channels[_filter][name][value]' => $channel['name'])
        );

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertEmpty($result['data']);
        $this->assertEmpty($result['options']['totalRecords']);
    }
}
