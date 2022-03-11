<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\ConsumptionExtension\MessageBodyResolverExtension;
use Oro\Component\MessageQueue\Client\MessageBodyResolverInterface;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Exception\InvalidMessageBodyException;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Topic\TopicRegistry;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class MessageBodyResolverExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC = 'sample.topic';
    private const BODY = ['sample_key' => 'sample_value'];

    private MessageBodyResolverInterface|\PHPUnit\Framework\MockObject\MockObject $messageBodyResolver;

    private TopicRegistry|\PHPUnit\Framework\MockObject\MockObject $topicRegistry;

    private MessageBodyResolverExtension $extension;

    private Context $context;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    protected function setUp(): void
    {
        $this->topicRegistry = $this->createMock(TopicRegistry::class);
        $this->messageBodyResolver = $this->createMock(MessageBodyResolverInterface::class);

        $this->extension = new MessageBodyResolverExtension($this->topicRegistry, $this->messageBodyResolver);

        $this->context = new Context($this->createMock(SessionInterface::class));
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context->setLogger($this->logger);
    }

    public function testOnPreReceivedDoesNothingIfStatusIsAlreadySet(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $message->setProperties([Config::PARAMETER_TOPIC_NAME => self::TOPIC]);

        $this->context->setMessage($message);
        $this->context->setStatus(MessageProcessorInterface::REJECT);

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Skipping message body resolving as message status is already set.',
                [
                    'messageId' => $message->getMessageId(),
                    'topic' => self::TOPIC,
                    'status' => $this->context->getStatus(),
                ]
            );

        $this->extension->onPreReceived($this->context);
    }

    public function testOnPreReceivedDoesNothingIfNoMessage(): void
    {
        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Skipping message body resolving as topic name is empty or is not present in topic registry.',
                ['messageId' => null, 'topic' => null]
            );

        $this->extension->onPreReceived($this->context);
    }

    public function testOnPreReceivedDoesNothingIfNoTopic(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');

        $this->context->setMessage($message);

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Skipping message body resolving as topic name is empty or is not present in topic registry.',
                ['messageId' => $message->getMessageId(), 'topic' => null]
            );

        $this->extension->onPreReceived($this->context);
    }

    public function testOnPreReceivedDoesNothingIfNoTopicService(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $message->setProperties([Config::PARAMETER_TOPIC_NAME => self::TOPIC]);

        $this->context->setMessage($message);

        $this->topicRegistry
            ->expects(self::once())
            ->method('has')
            ->with(self::TOPIC)
            ->willReturn(false);

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Skipping message body resolving as topic name is empty or is not present in topic registry.',
                ['messageId' => $message->getMessageId(), 'topic' => self::TOPIC]
            );

        $this->extension->onPreReceived($this->context);

        self::assertSame('', $message->getBody());
    }

    public function testOnPreReceivedSetsResolvedBody(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $message->setProperties([Config::PARAMETER_TOPIC_NAME => self::TOPIC]);
        $message->setBody(self::BODY);

        $this->context->setMessage($message);

        $this->topicRegistry
            ->expects(self::once())
            ->method('has')
            ->with(self::TOPIC)
            ->willReturn(true);

        $resolvedBody = self::BODY + ['resolved' => 1];
        $this->messageBodyResolver
            ->expects(self::once())
            ->method('resolveBody')
            ->with(self::TOPIC, self::BODY)
            ->willReturn($resolvedBody);

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Message body is resolved.',
                ['messageId' => $message->getMessageId(), 'topic' => self::TOPIC]
            );

        $this->extension->onPreReceived($this->context);

        self::assertSame($resolvedBody, $message->getBody());
    }

    public function testOnPreReceivedRejectsWhenInvalidBody(): void
    {
        $message = new Message();
        $message->setMessageId('sample-id');
        $message->setProperties([Config::PARAMETER_TOPIC_NAME => self::TOPIC]);
        $message->setBody(self::BODY);

        $this->context->setMessage($message);

        $this->topicRegistry
            ->expects(self::once())
            ->method('has')
            ->with(self::TOPIC)
            ->willReturn(true);

        $exception = InvalidMessageBodyException::create('Invalid body', self::TOPIC, self::BODY);
        $this->messageBodyResolver
            ->expects(self::once())
            ->method('resolveBody')
            ->with(self::TOPIC, self::BODY)
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains(
                    sprintf('Message is rejected. Message of topic "%s" has invalid body', self::TOPIC)
                ),
                [
                    'exception' => $exception,
                    'messageId' => $message->getMessageId(),
                    'topic' => self::TOPIC,
                    'message' => self::BODY,
                ]
            );

        $this->extension->onPreReceived($this->context);

        self::assertSame(self::BODY, $message->getBody());
        self::assertSame(MessageProcessorInterface::REJECT, $this->context->getStatus());
    }
}
