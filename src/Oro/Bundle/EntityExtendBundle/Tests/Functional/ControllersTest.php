<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ControllersTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = self::createClient(array(), $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->client->generate('oro_entityconfig_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->client->generate('oro_entityextend_entity_create'));
        $form = $crawler->selectButton('Save')->form();
        $form['oro_entity_config_type[model][className]'] = 'testExtendedEntity';
        $form['oro_entity_config_type[entity][label]'] = 'test entity label';
        $form['oro_entity_config_type[entity][plural_label]'] = 'test entity plural label';
        $form['oro_entity_config_type[entity][description]'] = 'test entity description';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Entity saved", $crawler->html());
        preg_match('/\/view\/(\d+)/', $this->client->getHistory()->current()->getUri(), $matches);
        $this->assertCount(2, $matches);
        return $matches[1];
    }

    /**
     * @depends testCreate
     * @param int $id
     * @return int
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->client->generate('oro_entityconfig_update', array('id' => $id))
        );

        $form = $crawler->selectButton('Save')->form();
        $form['oro_entity_config_type[entity][label]'] = 'test entity label updated';
        $form['oro_entity_config_type[entity][plural_label]'] = 'test entity plural label updated';
        $form['oro_entity_config_type[entity][description]'] = 'test entity description updated';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Entity saved", $crawler->html());

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView($id)
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_entityconfig_view', array('id' => $id))
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('test entity label updated', $result->getContent());

    }
}
