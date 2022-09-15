<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class GenuineSyncSchedulerTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithRegistryAsFirstArgument(): void
    {
        new GenuineSyncScheduler($this->createMock(MessageProducerInterface::class));
    }

    public function testShouldSendSyncIntegrationMessageWithIntegrationIdOnly(): void
    {
        $messageProducer = self::getMessageProducer();

        $scheduler = new GenuineSyncScheduler($messageProducer);

        $scheduler->schedule('theIntegrationId');

        self::assertMessageSent(
            SyncIntegrationTopic::getName(),
            [
                'integration_id' => 'theIntegrationId',
                'connector' => null,
                'connector_parameters' => [],
                'transport_batch_size' => 100,
            ]
        );
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }

    public function testShouldAllowPassConnectorNameAndOptions(): void
    {
        $messageProducer = self::getMessageProducer();

        $scheduler = new GenuineSyncScheduler($messageProducer);

        $scheduler->schedule('theIntegrationId', 'theConnectorName', ['theOption' => 'theValue']);

        self::assertMessageSent(
            SyncIntegrationTopic::getName(),
            [
                'integration_id' => 'theIntegrationId',
                'connector' => 'theConnectorName',
                'connector_parameters' => ['theOption' => 'theValue'],
                'transport_batch_size' => 100,
            ]
        );
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }
}
