<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestIntegrationTransport;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class IntegrationControllerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Channel::class);
    }

    private function getOrganization(): Organization
    {
        return $this->getContainer()->get('doctrine')->getRepository(Organization::class)
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION]);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Integrations - System', $crawler->html());
    }

    public function testShouldScheduleSyncJobForActiveIntegration()
    {
        $channel = $this->createChannel();
        $entityManager = $this->getEntityManager();
        $entityManager->persist($channel);
        $entityManager->flush();
        $this->ajaxRequest('POST', $this->getUrl('oro_integration_schedule', ['id' => $channel->getId()]));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertTrue($result['successful']);
        $this->assertNotEmpty($result['message']);

        $traces = self::getMessageCollector()->getTopicSentMessages(Topics::SYNC_INTEGRATION);
        $this->assertCount(1, $traces);
    }

    public function testCreate(): array
    {
        $this->markTestIncomplete('Skipped due to issue with dynamic form loading');

        /** @var User $user */
        $user = $this->getContainer()->get('security.token_storage')->getToken()->getUser();
        $newUser = clone $user;
        $newUser->setUsername('new username');
        $newUser->setEmail(mt_rand() . $user->getEmail());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($newUser);
        $entityManager->flush($newUser);

        $organization = $this->getOrganization();
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_create'));
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

        $name = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]'] = 'Simple channel';
        $form['oro_integration_channel_form[organization]'] = $organization->getId();
        $form['oro_integration_channel_form[type]'] = 'simple';
        $form['oro_integration_channel_form[defaultUserOwner]'] = $newUser->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Integration saved', $crawler->html());

        return compact('name', 'newUser', 'organization');
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(array $data): array
    {
        $response = $this->client->requestGrid(
            'oro-integration-grid',
            ['channels[_filter][name][value]' => $data['name']]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $integration = $result;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_integration_update', ['id' => $result['id']])
        );

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
        self::assertStringContainsString('Integration saved', $crawler->html());

        $integration['name'] = $name;
        return $integration;
    }

    public function testShouldNotScheduleSyncJobIfIntegrationNotActive()
    {
        $channel = $this->createChannel();
        $channel->setEnabled(false);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($channel);
        $entityManager->flush();

        $this->ajaxRequest('POST', $this->getUrl('oro_integration_schedule', ['id' => $channel->getId()]));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result['message']);
        $this->assertFalse($result['successful']);
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(array $integration)
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

    private function createChannel(): Channel
    {
        $channel = new Channel();
        $channel->setName('aName');
        $channel->setType('aType');
        $channel->setEnabled(true);
        $channel->setTransport(new TestIntegrationTransport());

        return $channel;
    }
}
