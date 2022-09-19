<?php

namespace Oro\Component\MessageQueue\Test\Async\Extension;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Psr\Log\LoggerInterface;

/**
 * Collects the processed messages to give the ability to check them in functional tests.
 */
class ConsumedMessagesCollectorExtension extends AbstractExtension
{
    /** @var array<string, Context> */
    private array $processed = [];

    private LoggerInterface $logger;

    private TestHandler $loggerTestHandler;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->loggerTestHandler = new TestHandler();
    }

    public function onStart(Context $context): void
    {
        if (!in_array($this->loggerTestHandler, $this->logger->getHandlers(), true)) {
            $this->logger->pushHandler($this->loggerTestHandler);
        }

        $context->setLogger($this->logger);
    }

    public function onPostReceived(Context $context): void
    {
        $this->processed[] = clone $context;
    }

    public function getLoggerTestHandler(): TestHandler
    {
        return $this->loggerTestHandler;
    }

    /**
     * @return list<array{topic: string, message: MessageInterface, context: Context}>
     */
    public function getProcessedMessages(): array
    {
        return array_map(static fn (Context $context) => [
            'topic' => $context->getMessage()->getProperty(MessageQueueConfig::PARAMETER_TOPIC_NAME),
            'message' => $context->getMessage(),
            'context' => $context,
        ], $this->processed);
    }

    public function clearProcessedMessages(): void
    {
        $this->processed = [];
    }

    public function clearProcessedMessagesByTopic(string $topic): void
    {
        $this->processed = array_filter(
            $this->processed,
            static fn (array $processedMessage) => $processedMessage['topic'] !== $topic
        );
    }
}
