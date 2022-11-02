<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\ConsumptionExtension\MessageProcessorRouterExtension;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\TopicMetaRegistry;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\Test\TestLogger;

class MessageProcessorRouterExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const NOOP_PROCESSOR = 'sample_noop_processor';

    private MessageProcessorRouterExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new MessageProcessorRouterExtension(
            new TopicMetaRegistry(
                ['sample_topic' => ['sample_queue']],
                ['sample_topic' => ['sample_queue' => 'sample_processor1']]
            ),
            new DestinationMetaRegistry(
                new Config('transport_prefix', 'default_queue'),
                ['sample_topic' => ['sample_queue']]
            ),
            'sample_noop_processor'
        );
    }

    public function testOnPreReceivedShouldDoNothingWhenMessageProcessorNameIsSet(): void
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessageProcessorName('foo_processor');

        $this->extension->onPreReceived($context);

        self::assertEquals('foo_processor', $context->getMessageProcessorName());
    }

    public function testOnPreReceivedShouldSetNoopProcessorNameIfProcessorNotFound(): void
    {
        $message = new Message();
        $message->setProperties(
            [Config::PARAMETER_TOPIC_NAME => 'foo_topic', Config::PARAMETER_QUEUE_NAME => 'foo_queue']
        );

        $context = new Context($this->createMock(SessionInterface::class));
        $logger = new TestLogger();
        $context->setLogger($logger);
        $context->setMessage($message);

        $this->extension->onPreReceived($context);

        self::assertEquals(self::NOOP_PROCESSOR, $context->getMessageProcessorName());
        self::assertTrue(
            $logger->hasWarning(
                'Message processor for "foo_topic" topic name in "foo_queue" queue was not found, '
                . 'falling back to "' . self::NOOP_PROCESSOR . '"'
            )
        );
    }

    public function testOnPreReceivedShouldSetNoopProcessorNameIfTopicNotSet(): void
    {
        $message = new Message();
        $message->setProperties([Config::PARAMETER_QUEUE_NAME => 'foo_queue']);

        $context = new Context($this->createMock(SessionInterface::class));
        $logger = new TestLogger();
        $context->setLogger($logger);
        $context->setMessage($message);

        $this->extension->onPreReceived($context);

        self::assertEquals(self::NOOP_PROCESSOR, $context->getMessageProcessorName());
        self::assertTrue(
            $logger->hasWarning(
                'Message processor for "" topic name in "foo_queue" queue was not found, '
                . 'falling back to "' . self::NOOP_PROCESSOR . '"'
            )
        );
    }

    public function testOnPreReceivedShouldSetNoopProcessorNameIfQueueNotSet(): void
    {
        $message = new Message();
        $message->setProperties([Config::PARAMETER_TOPIC_NAME => 'foo_topic']);

        $context = new Context($this->createMock(SessionInterface::class));
        $logger = new TestLogger();
        $context->setLogger($logger);
        $context->setMessage($message);

        $this->extension->onPreReceived($context);

        self::assertEquals(self::NOOP_PROCESSOR, $context->getMessageProcessorName());
        self::assertTrue(
            $logger->hasWarning(
                'Message processor for "foo_topic" topic name in "" queue was not found, '
                . 'falling back to "' . self::NOOP_PROCESSOR . '"'
            )
        );
    }

    public function testOnPreReceivedShouldSetNoopProcessorNameIfMessageNotSet(): void
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $logger = new TestLogger();
        $context->setLogger($logger);

        $this->extension->onPreReceived($context);

        self::assertEquals(self::NOOP_PROCESSOR, $context->getMessageProcessorName());
        self::assertTrue(
            $logger->hasWarning(
                'Message processor for "" topic name in "" queue was not found, '
                . 'falling back to "' . self::NOOP_PROCESSOR . '"'
            )
        );
    }

    public function testOnPreReceivedShouldSetFoundProcessorName(): void
    {
        $message = new Message();
        $message->setProperties(
            [Config::PARAMETER_TOPIC_NAME => 'sample_topic', Config::PARAMETER_QUEUE_NAME => 'sample_queue']
        );

        $context = new Context($this->createMock(SessionInterface::class));
        $logger = new TestLogger();
        $context->setLogger($logger);
        $context->setMessage($message);

        $this->extension->onPreReceived($context);

        self::assertEquals('sample_processor1', $context->getMessageProcessorName());
        self::assertTrue(
            $logger->hasDebug(
                'Found "sample_processor1" message processor for topic "sample_topic" in queue "sample_queue"'
            )
        );
    }
}
