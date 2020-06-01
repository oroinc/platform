<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\CallbackMessageBuilder;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException;
use Oro\Component\MessageQueue\Exception\TopicSubscriberNotFoundException;
use Oro\Component\MessageQueue\Router\RecipientListRouterInterface;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Util\JSON;

class MessageProducerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $driver;

    /** @var RecipientListRouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var DestinationMetaRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $destinationMetaRegistry;

    /** @var MessageProducer */
    private $producer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);
        $this->router = $this->createMock(RecipientListRouterInterface::class);
        $this->destinationMetaRegistry = $this->createMock(DestinationMetaRegistry::class);
        $this->producer = new MessageProducer($this->driver, $this->router, $this->destinationMetaRegistry);
    }

    /**
     * @dataProvider validMessageDataProvider
     *
     * @param mixed $message
     * @param Message $expectedMessage
     */
    public function testSend($message, Message $expectedMessage): void
    {
        $this->router
            ->expects($this->once())
            ->method('getTopicSubscribers')
            ->with('topic.name')
            ->willReturn([['processor.name', 'queue.name']]);

        $destinationMeta = new DestinationMeta('client.name', 'prefix.queue.name');
        $this->destinationMetaRegistry
            ->expects($this->once())
            ->method('getDestinationMeta')
            ->with('queue.name')
            ->willReturn($destinationMeta);

        $queue = new Queue('prefix.queue.name');
        $this->driver
            ->expects($this->once())
            ->method('createQueue')
            ->with('prefix.queue.name')
            ->willReturn($queue);
        $this->driver
            ->expects($this->once())
            ->method('send')
            ->with($queue, $this->callback(function (Message $innerMessage) use ($expectedMessage) {
                $innerMessage->setMessageId('message.id');
                $innerMessage->setTimestamp(1);

                $this->assertEquals($innerMessage, $expectedMessage);

                return true;
            }));

        $this->producer->send('topic.name', $message);
    }

    public function testSendWithFewSubscribers(): void
    {
        $message = new Message();
        $expectedMessage = $this->getExpectedMessage();

        $this->router
            ->expects($this->once())
            ->method('getTopicSubscribers')
            ->with('topic.name')
            ->willReturn([
                ['processor.name', 'queue1.name'],
                ['processor.name', 'queue2.name'],
            ]);

        $destinationMeta1 = new DestinationMeta('client.name', 'prefix.queue1.name');
        $destinationMeta2 = new DestinationMeta('client.name', 'prefix.queue2.name');
        $this->destinationMetaRegistry
            ->expects($this->exactly(2))
            ->method('getDestinationMeta')
            ->withConsecutive(
                ['queue1.name'],
                ['queue2.name']
            )
            ->willReturnOnConsecutiveCalls(
                $destinationMeta1,
                $destinationMeta2
            );

        $queue1 = new Queue('prefix.queue1.name');
        $queue2 = new Queue('prefix.queue2.name');
        $this->driver
            ->expects($this->exactly(2))
            ->method('createQueue')
            ->withConsecutive(
                ['prefix.queue1.name'],
                ['prefix.queue2.name']
            )
            ->willReturnOnConsecutiveCalls(
                $queue1,
                $queue2
            );

        $this->driver
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [$queue1, $this->callback(function (Message $innerMessage) use ($expectedMessage) {
                    $innerMessage->setMessageId('message.id');
                    $innerMessage->setTimestamp(1);
                    $expectedMessage->setProperty('oro.message_queue.client.queue_name', 'prefix.queue1.name');

                    $this->assertEquals($innerMessage, $expectedMessage);

                    return true;
                })],
                [$queue2, $this->callback(function (Message $innerMessage) use ($expectedMessage) {
                    $innerMessage->setMessageId('message.id');
                    $innerMessage->setTimestamp(1);
                    $expectedMessage->setProperty('oro.message_queue.client.queue_name', 'prefix.queue2.name');

                    $this->assertEquals($innerMessage, $expectedMessage);

                    return true;
                })]
            );

        $this->producer->send('topic.name', $message);
    }

    /**
     * @dataProvider invalidMessageDataProvider
     *
     * @param mixed $message
     * @param string $expectException
     * @param string $expectExceptionMessage
     */
    public function testSendWInvalidMessage($message, string $expectException, string $expectExceptionMessage): void
    {
        $this->router
            ->expects($this->once())
            ->method('getTopicSubscribers')
            ->with('topic.name')
            ->willReturn([['processor.name', 'queue.name']]);

        $destinationMeta = new DestinationMeta('client.name', 'prefix.queue.name');
        $this->destinationMetaRegistry
            ->expects($this->once())
            ->method('getDestinationMeta')
            ->with('queue.name')
            ->willReturn($destinationMeta);

        $this->expectException($expectException);
        $this->expectExceptionMessage($expectExceptionMessage);

        $this->producer->send('topic.name', $message);
    }

    public function testSendInvalidTopic(): void
    {
        $this->router
            ->expects($this->once())
            ->method('getTopicSubscribers')
            ->with('topic.name')
            ->willReturn([]);

        $this->destinationMetaRegistry
            ->expects($this->never())
            ->method('getDestinationMeta');

        $this->expectException(TopicSubscriberNotFoundException::class);
        $this->expectExceptionMessage('There is no message processors subscribed for topic "topic.name".');

        $this->producer->send('topic.name', new Message());
    }

    /**
     * @return array
     */
    public function validMessageDataProvider(): array
    {
        return [
            'message object with array body' => [
                'message' => new Message(['key' => 'value']),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setBody(JSON::encode(['key' => 'value']))
                    ->setContentType('application/json'),
            ],
            'message object with string body' => [
                'message' => new Message('string'),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setBody('string'),
            ],
            'message object with null' => [
                'message' => new Message(),
                'expectedMessage' => $this->getExpectedMessage(),
            ],
            'message object with priority' => [
                'message' => new Message(null, 'oro.message_queue.client.very_low_message_priority'),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setPriority('oro.message_queue.client.very_low_message_priority'),
            ],
            'message object with expire' => [
                'message' => (new Message())->setExpire(123),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setExpire(123),
            ],
            'message object with delay' => [
                'message' => (new Message())->setDelay(20),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setDelay(20),
            ],
            'message object with header' => [
                'message' => (new Message())->setHeader('test', 'header1'),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setHeader('test', 'header1'),
            ],
            'message object with property' => [
                'message' => (new Message())->setHeader('test', 'property1'),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setHeader('test', 'property1'),
            ],
            'message builder'              => [
                'message' => new CallbackMessageBuilder(function () {
                    return ['key' => 'value'];
                }),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setBody(JSON::encode(['key' => 'value']))
                    ->setContentType('application/json')
            ]
        ];
    }

    /**
     * @return array
     */
    public function invalidMessageDataProvider(): array
    {
        return [
            'invalid content type' => [
                'message' => (new Message([]))->setContentType('text/plain'),
                'expectException' => InvalidArgumentException::class,
                'expectExceptionMessage' => 'When body is array content type must be "application/json".',
            ],
            'invalid body' => [
                'message' => new Message(new \stdClass()),
                'expectException' => InvalidArgumentException::class,
                'expectExceptionMessage' => 'The message\'s body must be either null, scalar or array. Got: stdClass',
            ],
        ];
    }

    /**
     * @return Message
     */
    private function getExpectedMessage(): Message
    {
        $expectedMessage = new Message();
        $expectedMessage->setBody('');
        $expectedMessage->setContentType('text/plain');
        $expectedMessage->setMessageId('message.id');
        $expectedMessage->setTimestamp(1);
        $expectedMessage->setPriority('oro.message_queue.client.normal_message_priority');
        $expectedMessage->setProperties([
            'oro.message_queue.client.topic_name' => 'topic.name',
            'oro.message_queue.client.processor_name' => 'processor.name',
            'oro.message_queue.client.queue_name' => 'prefix.queue.name',
        ]);

        return $expectedMessage;
    }
}
