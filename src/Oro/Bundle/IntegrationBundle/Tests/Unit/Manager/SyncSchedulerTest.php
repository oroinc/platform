<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SyncSchedulerTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testCouldBeConstructedWithMessageProducerAsFirstArgument()
    {
        new SyncScheduler($this->createMock(MessageProducerInterface::class));
    }

    public function testShouldSendReversSyncIntegrationMessage()
    {
        $scheduler = new SyncScheduler(self::getMessageProducer());

        $scheduler->schedule('theIntegrationId', 'theConnectorName', ['connectorOption' => 'connectorOptionValue']);

        self::assertMessageSent(
            Topics::REVERS_SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id' => 'theIntegrationId',
                    'connector_parameters' => ['connectorOption' => 'connectorOptionValue'],
                    'connector' => 'theConnectorName',
                    'transport_batch_size' => 100,
                ],
                MessagePriority::VERY_LOW
            )
        );
    }
}
