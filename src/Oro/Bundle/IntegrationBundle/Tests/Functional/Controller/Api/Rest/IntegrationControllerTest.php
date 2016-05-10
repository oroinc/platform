<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class IntegrationControllerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->entityManager = $this->client->getContainer()->get('doctrine')
            ->getManagerForClass('OroIntegrationBundle:Channel');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->resetClient();
    }

    public function testShouldActivateIntegration()
    {
        $channel = $this->createChannel();
        $channel->setEnabled(false);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        $channelId = $channel->getId();

        $this->client->request('GET', $this->getUrl('oro_api_activate_integration', ['id' => $channelId]));

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));

        $channel = $this->findChannel($channel->getId());
        $this->assertTrue($channel->isEnabled());
    }

    public function testShouldDeactivateIntegration()
    {
        $channel = $this->createChannel();
        $channel->setEnabled(true);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        $channelId = $channel->getId();

        $this->client->request('GET', $this->getUrl('oro_api_deactivate_integration', ['id' => $channelId]));

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));

        $channel = $this->findChannel($channel->getId());
        $this->assertFalse($channel->isEnabled());
    }

    public function testShouldSetPreviouslyEnabledFieldOnActivate()
    {
        $channel = $this->createChannel();
        $channel->setEnabled(false);
        $channel->setPreviouslyEnabled(null);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        $channelId = $channel->getId();

        $this->client->request('GET', $this->getUrl('oro_api_activate_integration', ['id' => $channelId]));

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));

        $channel = $this->findChannel($channel->getId());
        $this->assertFalse($channel->getPreviouslyEnabled());
    }

    public function testShouldSetPreviouslyEnabledFieldsOnDeactivate()
    {
        $channel = $this->createChannel();
        $channel->setEnabled(true);
        $channel->setPreviouslyEnabled(null);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        $channelId = $channel->getId();

        $this->client->request('GET', $this->getUrl('oro_api_deactivate_integration', ['id' => $channelId]));

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));

        $channel = $this->findChannel($channel->getId());
        $this->assertTrue($channel->getPreviouslyEnabled());
    }

    public function testShouldScheduleSyncJobOnActivation()
    {
        //guard
        $this->assertCount(0, $this->getScheduledSyncJobs());

        $channel = $this->createChannel();
        $channel->setEnabled(true);
        $channel->setPreviouslyEnabled(null);

        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        $channelId = $channel->getId();

        $this->client->request('GET', $this->getUrl('oro_api_activate_integration', ['id' => $channelId]));

        $this->assertResult($this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->assertCount(1, $this->getScheduledSyncJobs());
    }

    /**
     * @param array $result
     */
    protected function assertResult($result)
    {
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * @param int $channelId
     *
     * @return Channel
     */
    protected function findChannel($channelId)
    {
        return $this->entityManager->getRepository('OroIntegrationBundle:Channel')->createQueryBuilder('c')
            ->where('c.id = :id')
            ->setParameter('id', $channelId)
            ->getQuery()
            ->getSingleResult()
        ;
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
        $channel->setPreviouslyEnabled(null);
        
        return $channel;
    }

    /**
     * @return array|Job[]
     */
    protected function getScheduledSyncJobs()
    {
        return $this->getContainer()->get('doctrine')->getRepository('JMSJobQueueBundle:Job')
            ->findBy(['command' => SyncCommand::COMMAND_NAME]);
    }
}
