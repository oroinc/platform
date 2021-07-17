<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\RedeliveryMessageExtension;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class RedeliveryMessageExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $driver;

    /** @var RedeliveryMessageExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);

        $this->extension = new RedeliveryMessageExtension($this->driver, 10);
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testOnPreReceived(array $properties, array $expectedProperties)
    {
        $session = $this->createMock(SessionInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $message = new TransportMessage();
        $message->setBody('test body');
        $message->setHeaders(['test headers']);
        $message->setProperties($properties);
        $message->setMessageId('test message id');
        $message->setRedelivered(true);

        $queue = new Queue('oro.default');
        $context = new Context($session);
        $context->setLogger($logger);
        $context->setMessage($message);
        $context->setQueueName('oro.default');

        $session->expects($this->once())
            ->method('createQueue')
            ->with('oro.default')
            ->willReturn($queue);

        $delayedMessage = new Message('test body');
        $delayedMessage->setHeaders(['test headers', 'message_id' => 'test message id']);
        $delayedMessage->setProperties($expectedProperties);
        $delayedMessage->setDelay(10);
        $delayedMessage->setMessageId('test message id');

        $this->driver
            ->expects($this->once())
            ->method('send')
            ->with($queue, $delayedMessage);

        $logger->expects($this->exactly(2))
            ->method('debug')
            ->will($this->returnValueMap([
                ['Send delayed message', []],
                ['Reject redelivered original message by setting reject status to context.', []],
            ]));

        $this->extension->onPreReceived($context);
        $this->assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());
    }

    public function testOnPreReceivedMessageDoesNotRedelivered()
    {
        $session = $this->createMock(SessionInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $message = new TransportMessage();

        $context = new Context($session);
        $context->setLogger($logger);
        $context->setMessage($message);

        $session->expects($this->never())
            ->method('createQueue');

        $logger->expects($this->never())
            ->method('debug');

        $this->driver
            ->expects($this->never())
            ->method('send');

        $this->extension->onPreReceived($context);
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            'without properties' => [
                'properties' => [],
                'expectedProperties' => ['oro-redeliver-count' => 1],
            ],
            'with extra property' => [
                'properties' => ['test properties'],
                'expectedProperties' => ['test properties', 'oro-redeliver-count' => 1],
            ],
            'with redeliver count' => [
                'properties' => ['oro-redeliver-count' => 5],
                'expectedProperties' => ['oro-redeliver-count' => 6],
            ],
        ];
    }
}
