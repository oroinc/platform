<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ChannelControllersTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    /**
     * @return \Oro\Bundle\OrganizationBundle\Entity\Organization|null
     */
    public function getOrganization()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroOrganizationBundle:Organization')
            ->findOneByName(LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_channel_index'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Integrations - System', $crawler->html());
    }

    public function testCreate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var User $user */
        $user    = $this->getContainer()->get('security.context')->getToken()->getUser();
        $newUser = clone $user;
        $newUser->setUsername('new username');
        $newUser->setEmail(mt_rand() . $user->getEmail());
        $em->persist($newUser);
        $em->flush($newUser);

        $organization = $this->getOrganization();

        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_channel_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $this->assertEquals(
            $user->getId(),
            $form['oro_integration_channel_form[defaultUserOwner]']->getValue(),
            'Should contains predefined default owner - current user'
        );

        $this->assertEquals(
            '',
            $form['oro_integration_channel_form[organization]']->getValue(),
            'Should contains predefined organization'
        );

        $name = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]'] = 'Simple channel';
        $form['oro_integration_channel_form[organization]'] = $organization->getId();
        $form['oro_integration_channel_form[type]'] = 'simple';
        $form['oro_integration_channel_form[defaultUserOwner]'] = $newUser->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Integration saved", $crawler->html());

        return compact('name', 'newUser', 'organization');
    }

    /**
     * @param array $data
     *
     * @depends testCreate
     *
     * @return array
     */
    public function testUpdate($data)
    {
        $response = $this->client->requestGrid(
            'channels-grid',
            array('channels[_filter][name][value]' => $data['name'])
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

        $this->assertEquals(
            $data['newUser']->getId(),
            $form['oro_integration_channel_form[defaultUserOwner]']->getValue(),
            'Should save default user owner'
        );

        $this->assertEquals(
            $data['organization']->getId(),
            $form['oro_integration_channel_form[organization]']->getValue(),
            'Should save organization'
        );

        $name = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]'] = $name;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Integration saved", $crawler->html());

        $channel['name'] = $name;
        return $channel;
    }

    /**
     * @param $channel
     *
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
     *
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
