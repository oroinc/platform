<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class GenuineSyncSchedulerTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithRegistryAsFirstArgument()
    {
        new GenuineSyncScheduler($this->createMock(MessageProducerInterface::class));
    }

    public function testShouldSendSyncIntegrationMessageWithIntegrationIdOnly()
    {
        $messageProducer = self::getMessageProducer();

        $scheduler = new GenuineSyncScheduler($messageProducer);

        $scheduler->schedule('theIntegrationId');

        self::assertMessageSent(
            Topics::SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id' => 'theIntegrationId',
                    'connector' => null,
                    'connector_parameters' => [],
                    'transport_batch_size' => 100,
                ],
                MessagePriority::VERY_LOW
            )
        );
    }

    public function testShouldAllowPassConnectorNameAndOptions()
    {
        $messageProducer = self::getMessageProducer();

        $scheduler = new GenuineSyncScheduler($messageProducer);

        $scheduler->schedule('theIntegrationId', 'theConnectorName', ['theOption' => 'theValue']);

        self::assertMessageSent(
            Topics::SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id' => 'theIntegrationId',
                    'connector' => 'theConnectorName',
                    'connector_parameters' => ['theOption' => 'theValue'],
                    'transport_batch_size' => 100,
                ],
                MessagePriority::VERY_LOW
            )
        );
    }
}
