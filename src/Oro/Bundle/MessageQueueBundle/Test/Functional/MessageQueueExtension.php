<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * It is expected that this trait will be used in classes that have "getContainer" static method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait MessageQueueExtension
{
    use MessageQueueAssertTrait;
    use MessageQueueConsumerTestTrait;

    /**
     * Removes all sent messages.
     *
     * @afterInitClient
     */
    public function setUpMessageCollector()
    {
        self::clearMessageCollector();
        self::purgeMessageQueue();
    }

    /** @return array<int, array{topic: string, message: MessageInterface, context: Context}> */
    protected function consumeMessages(int $sentMessagesCount = null, string $collectTopic = null): array
    {
        $result = [];

        if (is_null($sentMessagesCount)) {
            $sentMessagesCount = count(self::getSentMessages());
        }

        self::clearMessageCollector();

        self::consume($sentMessagesCount);

        foreach (self::getProcessedMessages() as $processedMessage) {
            if ($collectTopic === $processedMessage['message']->getProperty(Config::PARAMETER_TOPIC_NAME)) {
                $result[] = $processedMessage;
            }
        }

        self::clearProcessedMessages();

        return $result;
    }

    /** @return array<int, array{topic: string, message: MessageInterface, context: Context}> */
    protected function consumeAllMessages(string $collectTopic = null): array
    {
        $result = [];

        while ($sentMessagesCount = count(self::getSentMessages())) {
            $result = array_merge($result, $this->consumeMessages($sentMessagesCount, $collectTopic));
        }

        return $result;
    }

    /**
     * Removes all sent messages.
     *
     * After triggered after client removed
     *
     * @beforeResetClient
     */
    protected static function tearDownMessageCollector(): void
    {
        self::purgeMessageQueue();
        self::clearMessageCollector();
        self::clearProcessedMessages();
        self::disableMessageBuffering();
    }

    protected static function getBufferedMessageProducer(): BufferedMessageProducer
    {
        return self::getContainer()->get('oro_message_queue.client.buffered_message_producer');
    }

    /**
     * Enables the buffering of sent messages.
     */
    protected static function enableMessageBuffering(): void
    {
        $bufferedProducer = self::getBufferedMessageProducer();
        if (!$bufferedProducer->isBufferingEnabled()) {
            $bufferedProducer->enableBuffering();
        }
    }

    /**
     * Disables the buffering of sent messages.
     */
    protected static function disableMessageBuffering(): void
    {
        $bufferedProducer = self::getBufferedMessageProducer();
        if ($bufferedProducer->isBufferingEnabled()) {
            $bufferedProducer->disableBuffering();
        }
    }

    /**
     * Flushes buffered sent messages.
     */
    protected static function flushMessagesBuffer(): void
    {
        $bufferedProducer = self::getBufferedMessageProducer();
        if ($bufferedProducer->isBufferingEnabled()) {
            $bufferedProducer->flushBuffer();
        }
    }
}
