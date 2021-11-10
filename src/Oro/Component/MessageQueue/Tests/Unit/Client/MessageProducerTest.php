<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\CallbackMessageBuilder;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\Router\Envelope;
use Oro\Component\MessageQueue\Client\Router\MessageRouterInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Util\JSON;

class MessageProducerTest extends \PHPUnit\Framework\TestCase
{
    private DriverInterface|\PHPUnit\Framework\MockObject\MockObject $driver;

    private MessageRouterInterface|\PHPUnit\Framework\MockObject\MockObject $messageRouter;

    private MessageProducer $producer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);
        $this->messageRouter = $this->createMock(MessageRouterInterface::class);

        $this->producer = new MessageProducer($this->driver, $this->messageRouter);
    }

    /**
     * @dataProvider validMessageDataProvider
     *
     * @param mixed $message
     * @param Message $expectedMessage
     */
    public function testSend(mixed $message, Message $expectedMessage): void
    {
        $queue = new Queue('sample_queue');
        $this->messageRouter
            ->expects(self::once())
            ->method('handle')
            ->with(
                self::callback(function (Message $innerMessage) use ($expectedMessage) {
                    $innerMessage->setMessageId('message.id');
                    $innerMessage->setTimestamp(1);

                    $this->assertEquals($innerMessage, $expectedMessage);

                    return true;
                })
            )
            ->willReturn([new Envelope($queue, $expectedMessage)]);

        $this->driver
            ->expects(self::once())
            ->method('send')
            ->with(
                $queue,
                self::callback(function (Message $innerMessage) use ($expectedMessage) {
                    $innerMessage->setMessageId('message.id');
                    $innerMessage->setTimestamp(1);

                    $this->assertEquals($innerMessage, $expectedMessage);

                    return true;
                })
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
    public function testSendWInvalidMessage(
        mixed $message,
        string $expectException,
        string $expectExceptionMessage
    ): void {
        $this->messageRouter
            ->expects(self::never())
            ->method('handle');

        $this->expectException($expectException);
        $this->expectExceptionMessage($expectExceptionMessage);

        $this->producer->send('topic.name', $message);
    }

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
            'message builder' => [
                'message' => new CallbackMessageBuilder(function () {
                    return ['key' => 'value'];
                }),
                'expectedMessage' => $this->getExpectedMessage()
                    ->setBody(JSON::encode(['key' => 'value']))
                    ->setContentType('application/json'),
            ],
        ];
    }

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

    private function getExpectedMessage(): Message
    {
        $expectedMessage = new Message();
        $expectedMessage->setBody('');
        $expectedMessage->setContentType('text/plain');
        $expectedMessage->setMessageId('message.id');
        $expectedMessage->setTimestamp(1);
        $expectedMessage->setPriority('oro.message_queue.client.normal_message_priority');
        $expectedMessage->setProperties(['oro.message_queue.client.topic_name' => 'topic.name']);

        return $expectedMessage;
    }
}
