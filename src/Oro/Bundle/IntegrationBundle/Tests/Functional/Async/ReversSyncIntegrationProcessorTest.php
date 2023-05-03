<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\IntegrationBundle\Test\Provider\TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ReversSyncIntegrationProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    private EntityManagerInterface $channelManager;

    private SyncScheduler $syncScheduler;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadChannelData::class,
        ]);

        $this->channelManager = self::getContainer()->get('doctrine')->getManagerForClass(Channel::class);
        $this->syncScheduler = self::getContainer()->get('oro_integration.sync_scheduler');
    }

    public function testProcess(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');
        self::assertEmpty($integration->getStatuses()->toArray());

        $this->syncScheduler->schedule($integration->getId(), TestConnector::TYPE);

        self::consume();

        // get managed entity again after reset in consumer
        $integration = $this->getReference('oro_integration:foo_integration');

        $this->channelManager->refresh($integration);

        $statuses = $integration->getStatuses();
        self::assertEquals(1, $statuses->count());

        /** @var Status $status */
        $status = $statuses->first();

        self::assertEquals(TestConnector::TYPE, $status->getConnector());
        self::assertStringContainsString('Can\'t find job "integration_test_export"', $status->getMessage());
    }
}
