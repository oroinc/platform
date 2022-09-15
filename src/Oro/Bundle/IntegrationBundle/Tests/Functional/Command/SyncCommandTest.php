<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Command;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessagePriority;

/**
 * @dbIsolationPerTest
 */
class SyncCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
    }

    private function getJobRepository(): JobRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Job::class);
    }

    public function testShouldOutputHelpForTheCommand(): void
    {
        $result = self::runCommand(SyncCommand::getDefaultName(), ['--help']);

        self::assertStringContainsString('Usage: oro:cron:integration:sync [options]', $result);
    }

    public function testIsActive(): void
    {
        self::assertTrue(self::getContainer()->get(SyncCommand::class)->isActive());
    }

    public function testIsActiveAllDisabled(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(Channel::class);
        $em->createQueryBuilder()
            ->update(Channel::class, 'c')
            ->set('c.enabled', ':enabled')
            ->getQuery()
            ->execute(['enabled' => false]);

        self::assertFalse(self::getContainer()->get(SyncCommand::class)->isActive());
    }

    public function testIsActiveNoConnectors(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(Channel::class);
        $em->createQueryBuilder()
            ->update(Channel::class, 'c')
            ->set('c.connectors', ':emptyConnectors')
            ->setParameter('emptyConnectors', [], Types::ARRAY)
            ->getQuery()
            ->execute();

        self::assertFalse(self::getContainer()->get(SyncCommand::class)->isActive());
    }

    public function testShouldSendSyncIntegrationWithoutAnyAdditionalOptions(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $result = self::runCommand(SyncCommand::getDefaultName(), ['--integration='.$integration->getId()]);

        self::assertStringContainsString('Schedule sync for "Foo Integration" integration.', $result);

        self::assertMessageSent(SyncIntegrationTopic::getName(), [
            'integration_id' => $integration->getId(),
            'connector_parameters' => [],
            'connector' => null,
            'transport_batch_size' => 100,
        ]);
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }

    public function testShouldSendSyncIntegrationWithCustomConnectorAndOptions(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $result = self::runCommand(
            SyncCommand::getDefaultName(),
            [
                '--integration='.$integration->getId(),
                '--connector' => 'theConnector',
                'fooConnectorOption=fooValue',
                'barConnectorOption=barValue',
            ]
        );

        self::assertStringContainsString('Schedule sync for "Foo Integration" integration.', $result);

        self::assertMessageSent(SyncIntegrationTopic::getName(), [
            'integration_id' => $integration->getId(),
            'connector_parameters' => [
                'fooConnectorOption' => 'fooValue',
                'barConnectorOption' => 'barValue',
            ],
            'connector' => 'theConnector',
            'transport_batch_size' => 100,
        ]);
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }

    public function testShouldSendSyncIntegrationWithStaleJob(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $entity = new Job();
        $entity->setName('oro_integration:sync_integration:' . $integration->getId());
        $entity->setOwnerId('owner-id-1');
        $entity->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $entity->setUnique(true);
        $entity->setStatus(Job::STATUS_RUNNING);

        $jobHandler = self::getContainer()->get('oro_message_queue.job.manager');
        $jobHandler->saveJob($entity);

        self::assertNull(
            $this->getJobRepository()->findRootJobByJobNameAndStatuses(
                'oro_integration:sync_integration:'.$integration->getId(),
                [Job::STATUS_STALE]
            )
        );

        $result = self::runCommand(SyncCommand::getDefaultName(), ['--integration='.$integration->getId()]);

        self::assertStringContainsString('Schedule sync for "Foo Integration" integration.', $result);

        self::assertMessageSent(SyncIntegrationTopic::getName(), [
            'integration_id' => $integration->getId(),
            'connector_parameters' => [],
            'connector' => null,
            'transport_batch_size' => 100,
        ]);
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);

        self::assertNotEmpty(
            $this->getJobRepository()->findRootJobByJobNameAndStatuses(
                'oro_integration:sync_integration:'.$integration->getId(),
                [Job::STATUS_STALE]
            )
        );
    }
}
