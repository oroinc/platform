<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageConsumer;
use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

class MessageConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DelegateMessageProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $delegateProcessor;

    /**
     * @var DestinationMetaRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $destinationMetaRegistry;

    /**
     * @var QueueConsumer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consumer;

    /**
     * @var MessageConsumer
     */
    protected $messageConsumer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->consumer = $this->getMockBuilder(QueueConsumer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->delegateProcessor = $this->getMockBuilder(DelegateMessageProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->destinationMetaRegistry = $this->getMockBuilder(DestinationMetaRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageConsumer = new MessageConsumer(
            $this->consumer,
            $this->delegateProcessor,
            $this->destinationMetaRegistry
        );
    }

    public function testProcess()
    {
        /** @var ExtensionInterface $extension */
        $extension = $this->getMock(ExtensionInterface::class);

        $this->messageConsumer->addExtension($extension);

        $destinationMeta = new DestinationMeta('testClientName', 'testTransportName');
        $this->destinationMetaRegistry
            ->expects($this->once())
            ->method('getDestinationsMeta')
            ->willReturn([$destinationMeta]);

        $this->consumer->expects($this->once())
            ->method('bind')
            ->with('testTransportName', $this->delegateProcessor);

        $this->consumer->expects($this->once())
            ->method('consume')
            ->with(new ChainExtension([$extension]));

        $connection = $this->getMock(ConnectionInterface::class);
        $connection->expects($this->once())->method('close');
        $this->consumer->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->messageConsumer->consume();
    }
}
