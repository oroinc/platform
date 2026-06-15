<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption;

use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityLowTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityMediumTestTopic;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\WeightedRoundRobinQueueIterator;

/**
 * Shared infrastructure helpers for QueueConsumer consumption-mode functional tests.
 *
 * Queue topology used in all mode tests:
 *   - HIGH   queue: oro.test.priority.high   (first bound  – highest priority, Q1)
 *   - MEDIUM queue: oro.test.priority.medium (second bound – medium priority,  Q2)
 *   - LOW    queue: oro.test.priority.low    (third bound  – lowest priority,  Q3)
 *
 * Usage:
 *   1. Use this trait inside a class that also uses
 *      {@see \Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension}.
 *   2. Implement {@see getConsumptionMode()} to return the mode string for the class under test.
 */
trait ConsumptionModeTestTrait
{
    /**
     * Returns the consumption mode string to be passed to {@see QueueConsumer::setConsumptionMode()}.
     */
    abstract protected function getConsumptionMode(): string;

    // =========================================================================
    // setUp / tearDown
    // =========================================================================

    protected function setUp(): void
    {
        $this->initClient();
        $this->purgeQueues();
        self::clearProcessedMessages();
    }

    protected function tearDown(): void
    {
        $this->purgeQueues();
        self::clearProcessedMessages();
    }

    // =========================================================================
    // Consumer factory helpers
    // =========================================================================

    /**
     * Creates a QueueConsumer bound to HIGH (Q1) + MEDIUM (Q2),
     * configured in the consumption mode returned by {@see getConsumptionMode()}.
     *
     * Any previously bound queues are unbound first so the consumer can be reused
     * across multiple {@see QueueConsumer::consume()} calls within a single test.
     */
    private function createConsumerWith2Queues(): QueueConsumer
    {
        return $this->createConsumer(false);
    }

    /**
     * Creates a QueueConsumer bound to HIGH (Q1) + MEDIUM (Q2) + LOW (Q3),
     * configured in the consumption mode returned by {@see getConsumptionMode()}.
     */
    private function createConsumerWith3Queues(): QueueConsumer
    {
        return $this->createConsumer(true);
    }

    /**
     * Creates a QueueConsumer bound to HIGH (w=$highWeight) + MEDIUM (w=$mediumWeight),
     * configured in the consumption mode returned by {@see getConsumptionMode()}.
     * Useful for consumption modes that support per-queue weight settings (e.g. weighted-round-robin).
     */
    private function createConsumerWith2WeightedQueues(int $highWeight, int $mediumWeight): QueueConsumer
    {
        return $this->createConsumer(
            false,
            [WeightedRoundRobinQueueIterator::WEIGHT => $highWeight],
            [WeightedRoundRobinQueueIterator::WEIGHT => $mediumWeight]
        );
    }

    /**
     * Creates a QueueConsumer bound to HIGH (w=$highWeight) + MEDIUM (w=$mediumWeight) + LOW (w=$lowWeight),
     * configured in the consumption mode returned by {@see getConsumptionMode()}.
     * Useful for consumption modes that support per-queue weight settings (e.g. weighted-round-robin).
     */
    private function createConsumerWith3WeightedQueues(
        int $highWeight,
        int $mediumWeight,
        int $lowWeight
    ): QueueConsumer {
        return $this->createConsumer(
            true,
            [WeightedRoundRobinQueueIterator::WEIGHT => $highWeight],
            [WeightedRoundRobinQueueIterator::WEIGHT => $mediumWeight],
            [WeightedRoundRobinQueueIterator::WEIGHT => $lowWeight]
        );
    }

    /**
     * @param array<string,mixed> $highSettings   Extra settings forwarded to HIGH queue binding.
     * @param array<string,mixed> $mediumSettings Extra settings forwarded to MEDIUM queue binding.
     * @param array<string,mixed> $lowSettings    Extra settings forwarded to LOW queue binding (when included).
     */
    private function createConsumer(
        bool $includeLowQueue,
        array $highSettings = [],
        array $mediumSettings = [],
        array $lowSettings = [],
    ): QueueConsumer {
        $consumer = self::getConsumer();
        $highQueue = PriorityHighTestTopic::NAME;
        $mediumQueue = PriorityMediumTestTopic::NAME;
        $lowQueue = PriorityLowTestTopic::NAME;

        // Unbind any previously bound queues so the same consumer instance can be reused.
        $consumer->unbindQueues($highQueue, $mediumQueue, $lowQueue);

        $consumer->setConsumptionMode($this->getConsumptionMode());
        $consumer->bindQueue(PriorityHighTestTopic::NAME, $highSettings);
        $consumer->bindQueue(PriorityMediumTestTopic::NAME, $mediumSettings);
        if ($includeLowQueue) {
            $consumer->bindQueue(PriorityLowTestTopic::NAME, $lowSettings);
        }

        return $consumer;
    }

    // =========================================================================
    // Consumption runner helpers
    // =========================================================================

    /**
     * Runs consumption of exactly $count messages from the HIGH + MEDIUM queues
     * (or until $timeLimit seconds elapse).
     */
    private function consumeWith2Queues(int $count, int $timeLimit = 10): void
    {
        $this->runConsumer($this->createConsumerWith2Queues(), $count, $timeLimit);
    }

    /**
     * Runs consumption of exactly $count messages from the HIGH + MEDIUM + LOW queues
     * (or until $timeLimit seconds elapse).
     */
    private function consumeWith3Queues(int $count, int $timeLimit = 10): void
    {
        $this->runConsumer($this->createConsumerWith3Queues(), $count, $timeLimit);
    }

    /**
     * Runs consumption of exactly $count messages from HIGH (w=$highWeight) + MEDIUM (w=$mediumWeight) queues
     * (or until $timeLimit seconds elapse).
     * Useful for consumption modes that support per-queue weight settings (e.g. weighted-round-robin).
     */
    private function consumeWith2WeightedQueues(
        int $highWeight,
        int $mediumWeight,
        int $count,
        int $timeLimit = 10
    ): void {
        $this->runConsumer(
            $this->createConsumerWith2WeightedQueues($highWeight, $mediumWeight),
            $count,
            $timeLimit
        );
    }

    /**
     * Runs consumption of exactly $count messages from HIGH (w=$highWeight) + MEDIUM (w=$mediumWeight)
     * + LOW (w=$lowWeight) queues (or until $timeLimit seconds elapse).
     * Useful for consumption modes that support per-queue weight settings (e.g. weighted-round-robin).
     */
    private function consumeWith3WeightedQueues(
        int $highWeight,
        int $mediumWeight,
        int $lowWeight,
        int $count,
        int $timeLimit = 10
    ): void {
        $this->runConsumer(
            $this->createConsumerWith3WeightedQueues($highWeight, $mediumWeight, $lowWeight),
            $count,
            $timeLimit
        );
    }

    private function runConsumer(QueueConsumer $consumer, int $count, int $timeLimit): void
    {
        $consumer->consume(new ChainExtension([
            new LoggerExtension(self::getLogger()),
            new LimitConsumedMessagesExtension($count),
            new LimitConsumptionTimeExtension(new \DateTime('+' . $timeLimit . ' sec')),
        ]));
    }

    // =========================================================================
    // Message sending helpers
    // =========================================================================

    /**
     * Sends $count messages to $topicName, labeling them $labelPrefix1 … $labelPrefix$count.
     */
    private function sendMessages(string $topicName, int $count, string $labelPrefix): void
    {
        for ($i = 1; $i <= $count; $i++) {
            self::getMessageProducer()->send($topicName, ['label' => "$labelPrefix$i"]);
        }
    }

    private function purgeQueues(): void
    {
        self::purgeMessageQueue(PriorityHighTestTopic::NAME);
        self::purgeMessageQueue(PriorityMediumTestTopic::NAME);
        self::purgeMessageQueue(PriorityLowTestTopic::NAME);
    }

    // =========================================================================
    // Assertion helpers
    // =========================================================================

    /**
     * Returns the ordered list of queue names for all processed messages.
     *
     * @return list<string>
     */
    private function getProcessedQueueOrder(): array
    {
        return array_values(
            array_map(
                static fn (array $msg) => $msg['context']->getQueueName(),
                self::getProcessedMessages()
            )
        );
    }

    /**
     * Returns the zero-based positions at which $queueName appears in $queueOrder,
     * re-indexed from 0.
     *
     * Used by {@see SequentialExhaustiveConsumptionModeTest} and other test classes that verify
     * positional ordering invariants across multiple queue types (e.g. "all HIGH before any MEDIUM").
     *
     * @param list<string> $queueOrder
     * @return list<int>
     */
    private function getQueuePositions(array $queueOrder, string $queueName): array
    {
        return array_values(
            array_keys(
                array_filter($queueOrder, static fn (string $q) => $q === $queueName)
            )
        );
    }

    /**
     * Asserts that position $before appears earlier (smaller index) than position $after
     * in the processed-messages list.
     *
     * Used by {@see SequentialExhaustiveConsumptionModeTest} and other test classes that verify
     * strict ordering invariants between queue groups (e.g. last HIGH index < first MEDIUM index).
     */
    private static function assertPositionBefore(int $before, int $after, string $message = ''): void
    {
        self::assertLessThan($after, $before, $message);
    }
}
