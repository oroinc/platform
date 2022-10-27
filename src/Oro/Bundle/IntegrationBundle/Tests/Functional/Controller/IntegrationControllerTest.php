<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
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
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Channel::class);
    }

    private function getOrganization(): Organization
    {
        return self::getContainer()->get('doctrine')->getRepository(Organization::class)
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION]);
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_index'));
        $result = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Integrations - System', $crawler->html());
    }

    public function testShouldScheduleSyncJobForActiveIntegration(): void
    {
        $channel = $this->createChannel();
        $entityManager = $this->getEntityManager();
        $entityManager->persist($channel);
        $entityManager->flush();
        $this->ajaxRequest('POST', $this->getUrl('oro_integration_schedule', ['id' => $channel->getId()]));

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertNotEmpty($result);
        self::assertTrue($result['successful']);
        self::assertNotEmpty($result['message']);

        self::assertCountMessages(SyncIntegrationTopic::getName(), 1);
    }

    public function testCreate(): array
    {
        $this->markTestIncomplete('Skipped due to issue with dynamic form loading');

        /** @var User $user */
        $user = self::getContainer()->get('security.token_storage')->getToken()->getUser();
        $newUser = clone $user;
        $newUser->setUsername('new username');
        $newUser->setEmail(mt_rand() . $user->getEmail());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($newUser);
        $entityManager->flush($newUser);

        $organization = $this->getOrganization();
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_create'));
        $form = $crawler->selectButton('Save and Close')->form();

        self::assertEquals(
            $user->getId(),
            $form['oro_integration_channel_form[defaultUserOwner]']->getValue(),
            'Should contain predefined default owner - current user'
        );

        self::assertEquals(
            $organization->getId(),
            $form['oro_integration_channel_form[organization]']->getValue(),
            'Should contain predefined organization'
        );

        $name = 'name' . self::generateRandomString();
        $form['oro_integration_channel_form[name]'] = 'Simple channel';
        $form['oro_integration_channel_form[organization]'] = $organization->getId();
        $form['oro_integration_channel_form[type]'] = 'simple';
        $form['oro_integration_channel_form[defaultUserOwner]'] = $newUser->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
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

        $result = self::getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $integration = $result;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_integration_update', ['id' => $result['id']])
        );

        $form = $crawler->selectButton('Save and Close')->form();

        self::assertEquals(
            $data['newUser']->getId(),
            $form['oro_integration_channel_form[defaultUserOwner]']->getValue(),
            'Should save default user owner'
        );

        self::assertEquals(
            $data['organization']->getId(),
            $form['oro_integration_channel_form[organization]']->getValue(),
            'Should save organization'
        );

        $name = 'name' . self::generateRandomString();
        $form['oro_integration_channel_form[name]'] = $name;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200, 'text/html; charset=UTF-8');
        self::assertStringContainsString('Integration saved', $crawler->html());

        $integration['name'] = $name;

        return $integration;
    }

    public function testShouldNotScheduleSyncJobIfIntegrationNotActive(): void
    {
        $channel = $this->createChannel();
        $channel->setEnabled(false);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($channel);
        $entityManager->flush();

        $this->ajaxRequest('POST', $this->getUrl('oro_integration_schedule', ['id' => $channel->getId()]));

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertNotEmpty($result);
        self::assertNotEmpty($result['message']);
        self::assertFalse($result['successful']);
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(array $integration): void
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_integration', ['id' => $integration['id']])
        );

        $response = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($response, 204);

        $response = $this->client->requestGrid(
            'oro-integration-grid',
            ['channels[_filter][name][value]' => $integration['name']]
        );

        $result = self::getJsonResponseContent($response, 200);

        self::assertEmpty($result['data']);
        self::assertEmpty($result['options']['totalRecords']);
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
