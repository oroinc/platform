<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Router;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;
use Oro\Component\MessageQueue\Client\Router\Envelope;
use Oro\Component\MessageQueue\Client\Router\MessageRouter;
use Oro\Component\MessageQueue\Transport\Queue;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MessageRouterTest extends \PHPUnit\Framework\TestCase
{
    private const TRANSPORT_PREFIX = 'sample_prefix';
    private const QUEUE_NAME = 'sample_queue';

    private MessageRouter $messageRouter;

    protected function setUp(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver
            ->expects(self::any())
            ->method('createQueue')
            ->willReturnCallback(static fn ($queueName) => new Queue($queueName));

        $topicMetaRegistry = new TopicMetaRegistry(['sample_topic' => [self::QUEUE_NAME]], []);
        $destinationMetaRegistry = new DestinationMetaRegistry(
            new Config(self::TRANSPORT_PREFIX, 'default_queue'),
            []
        );

        $this->messageRouter = new MessageRouter(
            $driver,
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

    public function testHandle(): void
    {
        $topicName = 'sample_topic';
        $message = (new Message(['sample_key' => 'sample_value']))
            ->setProperty(Config::PARAMETER_TOPIC_NAME, $topicName);

        $expectedMessage = (new Message($message->getBody()))
            ->setProperties(
                [
                    Config::PARAMETER_TOPIC_NAME => $topicName,
                    Config::PARAMETER_QUEUE_NAME => self::TRANSPORT_PREFIX . '.' . self::QUEUE_NAME,
                ]
            );

        $result = $this->messageRouter->handle($message);
        $result = iterator_to_array($result);

        self::assertEquals([new Envelope(new Queue('sample_prefix.sample_queue'), $expectedMessage)], $result);
    }
}
