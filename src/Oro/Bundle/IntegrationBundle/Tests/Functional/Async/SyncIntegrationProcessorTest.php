<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\SyncIntegrationProcessor;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Test\Provider\TestConnector;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class SyncIntegrationProcessorTest extends WebTestCase
{
    private EntityManagerInterface $channelManager;

    private SyncIntegrationProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadChannelData::class,
        ]);

        $this->channelManager = self::getContainer()->get('doctrine')->getManagerForClass(Channel::class);
        $this->processor = self::getContainer()->get('oro_integration.async.sync_integration_processor');
    }

    public function testProcess(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');
        self::assertEmpty($integration->getStatuses()->toArray());

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody([
            'integration_id' => $integration->getId(),
            'connector' => null,
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ]);

        $this->processor->process($message, $this->createMock(SessionInterface::class));

        $this->channelManager->refresh($integration);

        $statuses = $integration->getStatuses();
        self::assertEquals(1, $statuses->count());

        /** @var Status $status */
        $status = $statuses->first();

        self::assertEquals(TestConnector::TYPE, $status->getConnector());
        self::assertStringContainsString('Can\'t find job "integration_test_import"', $status->getMessage());
    }

    public function testIntegrationTokenCreationAfterRunProcess(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('oro_integration:foo_integration');
        self::assertEmpty($integration->getStatuses()->toArray());

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody([
            'integration_id' => $integration->getId(),
            'connector' => null,
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ]);
        self::assertNull(self::getContainer()->get('security.token_storage')->getToken());

        $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(
            $integration->getOrganization(),
            self::getContainer()->get('security.token_storage')->getToken()->getOrganization()
        );
    }
}
