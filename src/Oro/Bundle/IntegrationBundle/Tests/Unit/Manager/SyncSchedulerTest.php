<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Async\Topic\ReverseSyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SyncSchedulerTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithMessageProducerAsFirstArgument(): void
    {
        new SyncScheduler($this->createMock(MessageProducerInterface::class));
    }

    public function testShouldSendReversSyncIntegrationMessage(): void
    {
        $scheduler = new SyncScheduler(self::getMessageProducer());

        $scheduler->schedule('theIntegrationId', 'theConnectorName', ['connectorOption' => 'connectorOptionValue']);

        self::assertMessageSent(
            ReverseSyncIntegrationTopic::getName(),
            [
                'integration_id' => 'theIntegrationId',
                'connector_parameters' => ['connectorOption' => 'connectorOptionValue'],
                'connector' => 'theConnectorName',

            ]
        );
        self::assertMessageSentWithPriority(ReverseSyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }
}
