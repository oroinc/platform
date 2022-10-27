<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Router;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;
use Oro\Component\MessageQueue\Client\Router\Envelope;
use Oro\Component\MessageQueue\Client\Router\MessageRouter;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use Oro\Component\MessageQueue\Transport\Queue;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MessageRouterTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC_NAME = 'sample.topic';
    private const TRANSPORT_PREFIX = 'sample_prefix';
    private const QUEUE_NAME = 'sample_queue';

    private MessageRouter $messageRouter;

    private TopicInterface|\PHPUnit\Framework\MockObject\MockObject $topic;

    protected function setUp(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver
            ->expects(self::any())
            ->method('createQueue')
            ->willReturnCallback(static fn ($queueName) => new Queue($queueName));

        $topicRegistry = $this->createMock(TopicRegistry::class);
        $this->topic = $this->createMock(TopicInterface::class);
        $topicRegistry
            ->expects(self::any())
            ->method('get')
            ->with(self::TOPIC_NAME)
            ->willReturn($this->topic);

        $topicMetaRegistry = new TopicMetaRegistry([self::TOPIC_NAME => [self::QUEUE_NAME]], []);
        $destinationMetaRegistry = new DestinationMetaRegistry(
            new Config(self::TRANSPORT_PREFIX, 'default_queue'),
            []
        );

        $this->messageRouter = new MessageRouter(
            $driver,
            $topicRegistry,
            $topicMetaRegistry,
            $destinationMetaRegistry
        );
    }

    public function testHandleWhenNoTopicName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Property "%s" was expected to be defined in a message', Config::PARAMETER_TOPIC_NAME)
        );

        $result = $this->messageRouter->handle(new Message());
        iterator_to_array($result);
    }

    public function testHandleSetsDefaultTopicPriorityWhenNotSetInMessage(): void
    {
        $message = (new Message(['sample_key' => 'sample_value']))
            ->setProperty(Config::PARAMETER_TOPIC_NAME, self::TOPIC_NAME);

        $this->topic
            ->expects(self::once())
            ->method('getDefaultPriority')
            ->with(self::QUEUE_NAME)
            ->willReturn(MessagePriority::LOW);

        $expectedMessage = (new Message($message->getBody()))
            ->setProperties(
                [
                    Config::PARAMETER_TOPIC_NAME => self::TOPIC_NAME,
                    Config::PARAMETER_QUEUE_NAME => self::TRANSPORT_PREFIX . '.' . self::QUEUE_NAME,
                ]
            )
            ->setPriority(MessagePriority::LOW);

        $result = $this->messageRouter->handle($message);
        $result = iterator_to_array($result);

        self::assertEquals([new Envelope(new Queue('sample_prefix.sample_queue'), $expectedMessage)], $result);
    }

    public function testHandleUsesMessagePriorityWhenSetInMessage(): void
    {
        $message = (new Message(['sample_key' => 'sample_value']))
            ->setProperty(Config::PARAMETER_TOPIC_NAME, self::TOPIC_NAME)
            ->setPriority(MessagePriority::HIGH);

        $this->topic
            ->expects(self::never())
            ->method('getDefaultPriority');

        $expectedMessage = (new Message($message->getBody()))
            ->setProperties(
                [
                    Config::PARAMETER_TOPIC_NAME => self::TOPIC_NAME,
                    Config::PARAMETER_QUEUE_NAME => self::TRANSPORT_PREFIX . '.' . self::QUEUE_NAME,
                ]
            )
            ->setPriority(MessagePriority::HIGH);

        $result = $this->messageRouter->handle($message);
        $result = iterator_to_array($result);

        self::assertEquals([new Envelope(new Queue('sample_prefix.sample_queue'), $expectedMessage)], $result);
    }
}
