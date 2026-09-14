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
    protected function consumeMessages(?int $sentMessagesCount = null, ?string $collectTopic = null): array
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

    /**
     * Consumes messages until no new ones are produced.
     *
     * Each round is capped by {@see MessageQueueConsumerTestTrait::consume()}, so a round that cannot reach its
     * message limit costs the whole time limit. Without an upper bound on the number of rounds a queue that keeps
     * producing messages, or one holding messages that cannot be received at all, would keep this loop running until
     * the test runner is killed. Exceeding the budget is therefore reported as a failure with the state of the queue,
     * which tells apart "still producing" from "messages are there but unreachable".
     *
     * @return array<int, array{topic: string, message: MessageInterface, context: Context}>
     */
    protected function consumeAllMessages(?string $collectTopic = null): array
    {
        $result = [];
        $rounds = 0;
        $startedAt = microtime(true);

        while ($sentMessagesCount = count(self::getSentMessages())) {
            $rounds++;
            $elapsed = microtime(true) - $startedAt;
            $roundBudgetExceeded = $rounds > static::getConsumeAllMessagesRoundLimit();
            if ($roundBudgetExceeded || $elapsed > static::getConsumeAllMessagesTimeLimit()) {
                $exceededBudget = $roundBudgetExceeded
                    ? sprintf('the round budget of %d', static::getConsumeAllMessagesRoundLimit())
                    : sprintf('the time budget of %d second(s)', static::getConsumeAllMessagesTimeLimit());
                self::fail(sprintf(
                    'Failed to consume all messages: %s was exceeded after %d round(s) and %.1f second(s)'
                    . ' with %d message(s) still pending.%sPending by topic:%s%s%sMessage queue:%s%s',
                    $exceededBudget,
                    $rounds - 1,
                    $elapsed,
                    $sentMessagesCount,
                    PHP_EOL,
                    PHP_EOL,
                    self::getPendingMessagesDump(),
                    PHP_EOL,
                    PHP_EOL,
                    self::getMessageQueueStateDump()
                ));
            }

            $result = array_merge($result, $this->consumeMessages($sentMessagesCount, $collectTopic));
        }

        return $result;
    }

    /**
     * Lists, per topic, the messages that were sent but not consumed yet. Together with the state of the queue this
     * tells a consumer that cannot receive anything apart from a producer that keeps feeding the loop: the queue
     * state alone shows how many messages are unreachable, this shows what keeps being produced.
     */
    private static function getPendingMessagesDump(): string
    {
        $topics = array_count_values(array_column(self::getSentMessages(), 'topic'));
        if (!$topics) {
            return '  none';
        }

        arsort($topics);

        $lines = [];
        foreach ($topics as $topic => $count) {
            $lines[] = sprintf('  %s: %d message(s)', $topic, $count);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * The number of consumption rounds {@see consumeAllMessages()} may run. A healthy drain needs a handful: one per
     * generation of messages produced while the previous generation was being consumed.
     */
    protected static function getConsumeAllMessagesRoundLimit(): int
    {
        return 200;
    }

    /**
     * The wall-clock budget, in seconds, of a single {@see consumeAllMessages()} call. It is checked between rounds,
     * so a call may overrun it by the duration of the round that was already running when the budget ran out.
     */
    protected static function getConsumeAllMessagesTimeLimit(): int
    {
        return 60;
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
