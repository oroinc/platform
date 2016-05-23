<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

/**
 * @dbIsolation
 */
class IntegrationControllerTest extends WebTestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->client->useHashNavigation(true);

        $this->entityManager = $this->client->getContainer()->get('doctrine')
            ->getManagerForClass('OroIntegrationBundle:Channel');
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
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_index'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Integrations - System', $crawler->html());
    }

    public function testCreate()
    {
        $this->markTestIncomplete('Skipped due to issue with dynamic form loading');

        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var User $user */
        $user    = $this->getContainer()->get('security.context')->getToken()->getUser();
        $newUser = clone $user;
        $newUser->setUsername('new username');
        $newUser->setEmail(mt_rand() . $user->getEmail());
        $entityManager->persist($newUser);
        $entityManager->flush($newUser);

        $organization = $this->getOrganization();
        $crawler      = $this->client->request('GET', $this->getUrl('oro_integration_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $this->assertEquals(
            $user->getId(),
            $form['oro_integration_channel_form[defaultUserOwner]']->getValue(),
            'Should contain predefined default owner - current user'
        );

        $this->assertEquals(
            $organization->getId(),
            $form['oro_integration_channel_form[organization]']->getValue(),
            'Should contain predefined organization'
        );

        $name                                                   = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]']             = 'Simple channel';
        $form['oro_integration_channel_form[organization]']     = $organization->getId();
        $form['oro_integration_channel_form[type]']             = 'simple';
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
            'oro-integration-grid',
            ['channels[_filter][name][value]' => $data['name']]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $integration = $result;
        $crawler     = $this->client->request(
            'GET',
            $this->getUrl('oro_integration_update', ['id' => $result['id']])
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

        $name                                       = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]'] = $name;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Integration saved", $crawler->html());

        $integration['name'] = $name;
        return $integration;
    }

    public function testShouldScheduleSyncJobIfIntegrationActive()
    {
        $channel = $this->createChannel();
        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        $this->client->request('GET', $this->getUrl('oro_integration_schedule', ['id' => $channel->getId()]));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertTrue($result['successful']);
        $this->assertNotEmpty($result['job_id']);
    }

    public function testShouldNotScheduleSyncJobIfIntegrationNotActive()
    {
        $channel = $this->createChannel();
        $channel->setEnabled(false);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        $this->client->request('GET', $this->getUrl('oro_integration_schedule', ['id' => $channel->getId()]));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 400);


        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result['message']);
        $this->assertFalse($result['successful']);
    }

    /**
     * @param $integration
     *
     * @depends testUpdate
     */
    public function testDelete($integration)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_integration', ['id' => $integration['id']])
        );

        $response = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($response, 204);

        $response = $this->client->requestGrid(
            'oro-integration-grid',
            ['channels[_filter][name][value]' => $integration['name']]
        );

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertEmpty($result['data']);
        $this->assertEmpty($result['options']['totalRecords']);
    }

    /**
     * @return Channel
     */
    protected function createChannel()
    {
        $channel = new Channel();
        $channel->setName('aName');
        $channel->setType('aType');
        $channel->setEnabled(true);

        return $channel;
    }
}
