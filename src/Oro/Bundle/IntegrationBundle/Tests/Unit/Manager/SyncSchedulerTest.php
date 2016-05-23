<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SyncSchedulerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithMessageProducerAsFirstArgument()
    {
        new SyncScheduler($this->createMessageProducerMock());
    }

    public function testShouldSendReversSyncIntegrationMessage()
    {
        $messageProducerMock = $this->createMessageProducerMock();
        $messageProducerMock
            ->expects($this->once())
            ->method('send')
            ->with(
                Topics::REVERS_SYNC_INTEGRATION,
                [
                    'integrationId' => 'theIntegrationId',
                    'connector_parameters' => ['connectorOption' => 'connectorOptionValue'],
                    'connector' => 'theConnectorName',
                    'transport_batch_size' => 100,
                ],
                MessagePriority::VERY_LOW
            )
        ;

        $scheduler = new SyncScheduler($messageProducerMock);

        $scheduler->schedule('theIntegrationId', 'theConnectorName', ['connectorOption' => 'connectorOptionValue']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }
}
