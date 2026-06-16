<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\WeightedRoundRobinQueueIterator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class WeightedRoundRobinQueueIteratorTest extends TestCase
{
    public function testRewindWithEmptyQueueListMakesIteratorInvalid(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([]);

        $iterator->rewind();

        self::assertFalse($iterator->valid());
    }

    public function testRewindWithEmptyQueueListDoesNotCallLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())
            ->method('debug');

        $iterator = new WeightedRoundRobinQueueIterator([]);
        $iterator->setLogger($logger);

        $iterator->rewind();
    }

    public function testSingleQueueWeightOneAfterMessageConsumedBecomesInvalid(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 1],
        ]);

        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testSingleQueueWeightOneIdleBecomesInvalid(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 1],
        ]);

        $iterator->rewind();

        self::assertTrue($iterator->valid());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testSingleQueueWeightThreeStaysValidUntilWeightReached(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 3],
        ]);

        $iterator->rewind();

        // Message 1 – count becomes 1, threshold not reached
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertTrue($iterator->valid());

        // Message 2 – count becomes 2, threshold not reached
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertTrue($iterator->valid());

        // Message 3 – count becomes 3, threshold reached → advance → isDone
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertFalse($iterator->valid());
    }

    public function testSingleQueueWeightThreeIdleBeforeWeightReachedBecomesInvalid(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 3],
        ]);

        $iterator->rewind();

        // Message 1 – count becomes 1, stays
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertTrue($iterator->valid());

        // Idle – advance → isDone
        $iterator->notifyIdle();
        $iterator->next();
        self::assertFalse($iterator->valid());
    }

    public function testTwoQueuesWeightTwoFullCycle(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 2],
            'q2' => ['weight' => 2],
        ]);

        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame(['weight' => 2], $iterator->current());

        // Q1 message 1 – stays on Q1
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());

        // Q1 message 2 – weight reached → advance to Q2
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertTrue($iterator->valid());
        self::assertSame('q2', $iterator->key());
        self::assertSame(['weight' => 2], $iterator->current());

        // Q2 message 1 – stays on Q2
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertTrue($iterator->valid());
        self::assertSame('q2', $iterator->key());

        // Q2 message 2 – weight reached → advance → isDone
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertFalse($iterator->valid());
    }

    public function testRewindAfterCompletedCycleRestartsIteration(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 1],
            'q2' => ['weight' => 1],
        ]);

        // Complete a full cycle
        $iterator->rewind();
        $iterator->notifyMessageReceived();
        $iterator->next();
        $iterator->notifyMessageReceived();
        $iterator->next();
        self::assertFalse($iterator->valid());

        // Rewind should restart
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
    }

    public function testMissingWeightKeyDefaultsToOne(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => [QueueConsumer::PROCESSOR => 'test'],
        ]);

        $iterator->rewind();

        self::assertTrue($iterator->valid());

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testWeightZeroIsClampedToOne(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => '0'],
        ]);

        $iterator->rewind();

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testNegativeWeightIsClampedToOne(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => '-5'],
        ]);

        $iterator->rewind();

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testLoggerReceivesDebugMessageOnRewind(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Starting a new weighted-round-robin cycle; first queue: "{queue}" (weight: {weight}).',
                ['queue' => 'q1', 'weight' => 1]
            );

        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 1],
        ]);
        $iterator->setLogger($logger);

        $iterator->rewind();
    }

    public function testLoggerReceivesDebugMessageWhenWeightReachedOnLastQueue(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Starting a new weighted-round-robin cycle; first queue: "{queue}" (weight: {weight}).',
                ['queue' => 'q1', 'weight' => 1]
            );

        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 1],
        ]);
        $iterator->setLogger($logger);

        $iterator->rewind();

        // Replace logger after rewind so only the advance message is captured.
        $logger2 = $this->createMock(LoggerInterface::class);
        $logger2->expects(self::once())
            ->method('debug')
            ->with(
                'Queue "{queue}" weight {weight} reached; cycle complete.',
                ['queue' => 'q1', 'weight' => 1]
            );
        $iterator->setLogger($logger2);

        $iterator->notifyMessageReceived();
        $iterator->next();
    }

    public function testLoggerReceivesDebugMessageWhenIdleOnLastQueue(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 2],
        ]);

        $iterator->rewind();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Queue "{queue}" idle; cycle complete.',
                ['queue' => 'q1']
            );
        $iterator->setLogger($logger);

        $iterator->notifyIdle();
        $iterator->next();
    }

    public function testLoggerReceivesDebugMessageWhenStayingWithinBudget(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 2],
        ]);

        $iterator->rewind();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Consumed message {count}/{weight} from queue "{queue}"; staying.',
                ['queue' => 'q1', 'count' => 1, 'weight' => 2]
            );
        $iterator->setLogger($logger);

        $iterator->notifyMessageReceived();
        $iterator->next();
    }

    public function testLoggerReceivesDebugMessageWhenWeightReachedAndSwitchingToNextQueue(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 1],
            'q2' => ['weight' => 3],
        ]);

        $iterator->rewind();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Queue "{queue}" weight {weight} reached; switching to "{nextQueue}" (weight: {nextWeight}).',
                ['queue' => 'q1', 'weight' => 1, 'nextQueue' => 'q2', 'nextWeight' => 3]
            );
        $iterator->setLogger($logger);

        $iterator->notifyMessageReceived();
        $iterator->next();
    }

    public function testLoggerReceivesDebugMessageWhenIdleAndSwitchingToNextQueue(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 2],
            'q2' => ['weight' => 3],
        ]);

        $iterator->rewind();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Queue "{queue}" idle; switching to "{nextQueue}" (weight: {nextWeight}).',
                ['queue' => 'q1', 'nextQueue' => 'q2', 'nextWeight' => 3]
            );
        $iterator->setLogger($logger);

        $iterator->notifyIdle();
        $iterator->next();
    }

    public function testLoggerReceivesDebugMessageWhenCycleCompleteAfterWeightReached(): void
    {
        $iterator = new WeightedRoundRobinQueueIterator([
            'q1' => ['weight' => 1],
        ]);

        $iterator->rewind();

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Queue "{queue}" weight {weight} reached; cycle complete.',
                ['queue' => 'q1', 'weight' => 1]
            );
        $iterator->setLogger($logger);

        $iterator->notifyMessageReceived();
        $iterator->next();
    }
}
