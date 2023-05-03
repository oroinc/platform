<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Test\Provider\TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SyncIntegrationProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    private EntityManagerInterface $channelManager;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadChannelData::class,
        ]);

        $this->channelManager = self::getContainer()->get('doctrine')->getManagerForClass(Channel::class);
    }

    public function testProcess(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');
        self::assertEmpty($integration->getStatuses()->toArray());

        $params = [
            '--connector' => TestConnector::TYPE,
            '--integration' => (string)$integration->getId()
        ];

        self::runCommand(SyncCommand::getDefaultName(), $params);

        self::consume();

        // get managed entity again after reset in consumer
        $integration = $this->getIntegration();

        $this->channelManager->refresh($integration);

        $statuses = $integration->getStatuses();
        self::assertEquals(1, $statuses->count());

        /** @var Status $status */
        $status = $statuses->first();

        self::assertEquals(TestConnector::TYPE, $status->getConnector());
        self::assertStringContainsString('Can\'t find job "integration_test_import"', $status->getMessage());
    }

    private function getIntegration(): Channel
    {
        return $this->getReference('oro_integration:foo_integration');
    }
}
