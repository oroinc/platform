<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorInterface;
use Oro\Component\MessageQueue\Consumption\QueueIterator\SequentialExhaustiveQueueIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class SequentialExhaustiveQueueIteratorTest extends TestCase
{
    private LoggerInterface&MockObject $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testImplementsNotifiableQueueIteratorInterface(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(['q1' => [QueueConsumer::PROCESSOR => 'proc1']]);

        self::assertInstanceOf(NotifiableQueueIteratorInterface::class, $iterator);
    }

    public function testEmptyBoundQueuesRewindMakesIteratorInvalid(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator([]);
        $iterator->rewind();

        self::assertFalse($iterator->valid());
    }

    public function testSingleQueueCycleEndsAfterOneIdlePoll(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(['q1' => [QueueConsumer::PROCESSOR => 'proc1']]);
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testSingleQueueRewindRestartsCycle(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(['q1' => [QueueConsumer::PROCESSOR => 'p1']]);
        $iterator->rewind();

        self::assertTrue($iterator->valid());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());

        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame([QueueConsumer::PROCESSOR => 'p1'], $iterator->current());
    }

    public function testStaysOnCurrentQueueWhileMessagesReceived(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']]
        );
        $iterator->rewind();

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());
        self::assertTrue($iterator->valid());

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());
        self::assertTrue($iterator->valid());

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());
        self::assertTrue($iterator->valid());
    }

    public function testAdvancesToNextQueueAfterIdleNotification(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']]
        );
        $iterator->rewind();

        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());
        self::assertTrue($iterator->valid());
    }

    public function testFullCycleWithThreeQueuesNoMessages(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator([
            'q1' => ['processor' => 'p1'],
            'q2' => ['processor' => 'p2'],
            'q3' => ['processor' => 'p3'],
        ]);
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame(['processor' => 'p1'], $iterator->current());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q2', $iterator->key());
        self::assertSame(['processor' => 'p2'], $iterator->current());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q3', $iterator->key());
        self::assertSame(['processor' => 'p3'], $iterator->current());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testQueueIsDrainedCompletelyBeforeAdvancing(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->rewind();

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());
    }

    public function testKeyAndCurrentReturnCorrectValuesForEachQueue(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator([
            'queue-a' => ['processor' => 'proc1'],
            'queue-b' => ['processor' => 'proc2'],
        ]);
        $iterator->rewind();

        self::assertSame('queue-a', $iterator->key());
        self::assertSame(['processor' => 'proc1'], $iterator->current());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('queue-b', $iterator->key());
        self::assertSame(['processor' => 'proc2'], $iterator->current());
    }

    public function testRewindResetsIsDoneFlagAfterFullCycle(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2']]
        );
        $iterator->rewind();

        $iterator->notifyIdle();
        $iterator->next(); // advance to q2
        $iterator->notifyIdle();
        $iterator->next(); // cycle complete

        self::assertFalse($iterator->valid());

        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
    }

    public function testNotifyIdleFollowedByNotifyMessageReceivedBeforeNextStaysOnCurrentQueue(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2']]
        );
        $iterator->rewind();

        $iterator->notifyIdle();
        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());
        self::assertTrue($iterator->valid());
    }

    public function testRewindLogsStartingNewCycleWithFirstQueueName(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2']]
        );
        $iterator->setLogger($this->logger);

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(
                'Starting a new sequential-exhaustive cycle; first queue: "{queue}".',
                ['queue' => 'q1']
            );

        $iterator->rewind();
    }

    public function testRewindDoesNotLogWhenNoQueues(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator([]);
        $iterator->setLogger($this->logger);

        $this->logger->expects(self::never())
            ->method('debug');

        $iterator->rewind();
    }

    public function testNextLogsContinuingToDrainCurrentQueue(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2']]
        );
        $iterator->setLogger($this->logger);
        $iterator->rewind();

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(
                'Continuing to drain queue "{queue}"; last poll had a message.',
                ['queue' => 'q1']
            );

        $iterator->notifyMessageReceived();
        $iterator->next();
    }

    public function testNextLogsQueueExhaustedAndSwitchingToNextQueue(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2']]
        );
        $iterator->setLogger($this->logger);
        $iterator->rewind();

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(
                'Queue "{prevQueue}" exhausted; switching to "{nextQueue}".',
                ['prevQueue' => 'q1', 'nextQueue' => 'q2']
            );

        $iterator->notifyIdle();
        $iterator->next();
    }

    public function testNextLogsAllQueuesExhaustedWhenCycleComplete(): void
    {
        $iterator = new SequentialExhaustiveQueueIterator(
            ['q1' => ['processor' => 'proc1'], 'q2' => ['processor' => 'proc2']]
        );
        $iterator->setLogger($this->logger);
        $iterator->rewind();

        $iterator->notifyIdle();
        $iterator->next();

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('All queues exhausted - sequential-exhaustive cycle complete.');

        $iterator->notifyIdle();
        $iterator->next();
    }
}
