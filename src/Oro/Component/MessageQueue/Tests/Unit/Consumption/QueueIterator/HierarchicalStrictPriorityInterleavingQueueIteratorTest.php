<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\HierarchicalStrictPriorityInterleavingQueueIterator;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorInterface;
use PHPUnit\Framework\TestCase;

final class HierarchicalStrictPriorityInterleavingQueueIteratorTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private HierarchicalStrictPriorityInterleavingQueueIterator $iterator;

    #[\Override]
    protected function setUp(): void
    {
        $this->iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => [QueueConsumer::PROCESSOR => 'proc1'],
            'q2' => [QueueConsumer::PROCESSOR => 'proc2'],
        ]);
        $this->setUpLoggerMock($this->iterator);
    }

    public function testImplementsNotifiableQueueIteratorInterface(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1']]
        );

        self::assertInstanceOf(NotifiableQueueIteratorInterface::class, $iterator);
    }

    public function testEmptyBoundQueuesIsInvalidAfterRewind(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([]);
        $iterator->rewind();

        self::assertFalse($iterator->valid());
    }

    public function testSingleQueueIsValidAfterRewindWithCorrectKeyAndCurrent(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1']]
        );
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame([QueueConsumer::PROCESSOR => 'proc1'], $iterator->current());
    }

    public function testSingleQueueCycleEndsAfterIdlePoll(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1']]
        );
        $iterator->rewind();

        self::assertTrue($iterator->valid());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testSingleQueueRewindRestartsCycleAfterEnd(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1']]
        );
        $iterator->rewind();

        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());

        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame([QueueConsumer::PROCESSOR => 'proc1'], $iterator->current());
    }

    public function testSingleQueueDrainsWhenMessageReceivedStaysOnSameQueue(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => ['processor' => 'proc2']]
        );
        $iterator->rewind();

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
    }

    public function testTwoQueuesNoMessagesVisitsBothQueuesAndEnds(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
        ]);
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());

        // Q1 - idle, advance to Q2
        $iterator->notifyIdle();
        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q2', $iterator->key());

        // Q2 - idle, done
        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testTwoQueuesDrainsQ1BeforeMovingToQ2ThenEnds(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
        ]);
        $iterator->rewind();

        // Q1 drains two messages
        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());

        // Q1 idle, advance to Q2
        $iterator->notifyIdle();
        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q2', $iterator->key());

        // Q2 - one poll (no notify = default idle), cycle ends
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testTwoQueuesMessageOnQ2JumpsBackToQ1(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
        ]);
        $iterator->rewind();

        // Q1 idle, advance to Q2
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());

        // Q2 receives message, go back to Q1
        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
    }

    public function testThreeQueuesNoMessagesVisitsAllQueuesInOrderAndEnds(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->rewind();

        self::assertSame('q1', $iterator->key());

        // Q1 idle, advance to Q2
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());

        // Q2 idle, advance to Q3
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q3', $iterator->key());

        // Q3 idle, done
        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testThreeQueuesQ1DrainsThenQ2IdleQ3IdleEnds(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->rewind();

        // Q1 drains two messages
        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());

        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());

        // Q1 idle, advance to Q2
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());

        // Q2 idle, advance to Q3
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q3', $iterator->key());

        // Q3 idle, done
        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testThreeQueuesQ1IdleThenQ2MessageGoesBackToQ1ThenAllIdleDone(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->rewind();

        // Q1 idle, advance to Q2
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());

        // Q2 receives message, go back to Q1
        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());

        // Q1 idle, advance to Q2
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());

        // Q2 idle, advance to Q3
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q3', $iterator->key());

        // Q3 idle, done
        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testFourQueuesNoMessagesVisitsAllFourQueuesAndEnds(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
            'q4' => ['processor' => 'proc4'],
        ]);
        $iterator->rewind();

        self::assertSame('q1', $iterator->key());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q3', $iterator->key());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q4', $iterator->key());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testRewindResetsDoneFlagAndRestartsAfterFullCycle(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
        ]);
        $iterator->rewind();

        // Complete the full cycle
        $iterator->notifyIdle();
        $iterator->next(); // advance to q2
        $iterator->notifyIdle();
        $iterator->next(); // done

        self::assertFalse($iterator->valid());

        // Rewind should fully reset state
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame(['processor' => 'proc1'], $iterator->current());
    }

    public function testLastNotifyBeforeNextWinsWhenIdleThenMessageReceived(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator(['q1' => ['processor' => 'proc1']]);
        $iterator->rewind();

        // notifyIdle then notifyMessageReceived; last call wins (message received = true)
        $iterator->notifyIdle();
        $iterator->notifyMessageReceived();
        $iterator->next();

        // Stays on Q1 because last notify was message received
        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
    }

    public function testKeyAndCurrentReturnCorrectValuesThroughCycle(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'high-queue' => ['param' => 'high-proc'],
            'lower-queue' => ['param' => 'lower-proc'],
        ]);
        $iterator->rewind();

        // High-priority phase
        self::assertSame('high-queue', $iterator->key());
        self::assertSame(['param' => 'high-proc'], $iterator->current());

        // Transition to lower-priority phase
        $iterator->notifyIdle();
        $iterator->next();

        // Lower-priority phase
        self::assertSame('lower-queue', $iterator->key());
        self::assertSame(['param' => 'lower-proc'], $iterator->current());
    }

    public function testRewindLogsStartingNewHierarchicalCycle(): void
    {
        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'Starting a new hierarchical-strict-priority-interleaving cycle; high-priority queue: "{queue}".',
                ['queue' => 'q1']
            );

        $this->iterator->rewind();
    }

    public function testRewindDoesNotLogWhenNoQueues(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([]);
        $iterator->setLogger($this->loggerMock);

        $this->loggerMock->expects(self::never())
            ->method('debug');

        $iterator->rewind();
    }

    public function testNextLogsContinuingToDrainHighPriorityQueueWhenMessageReceived(): void
    {
        $this->iterator->rewind();

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'Continuing to drain high-priority queue "{queue}"; last poll had a message.',
                ['queue' => 'q1']
            );

        $this->iterator->notifyMessageReceived();
        $this->iterator->next();
    }

    public function testNextLogsHighPriorityQueueExhaustedSwitchingToNextQueue(): void
    {
        $this->iterator->rewind();

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'High-priority queue "{queue}" exhausted; switching to queue "{nextQueue}".',
                ['queue' => 'q1', 'nextQueue' => 'q2']
            );

        $this->iterator->notifyIdle();
        $this->iterator->next();
    }

    public function testNextLogsHighPriorityQueueExhaustedNoFurtherQueuesCycleComplete(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator(['q1' => ['processor' => 'proc1']]);
        $iterator->setLogger($this->loggerMock);
        $iterator->rewind();

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'High-priority queue "{queue}" exhausted; no further queues - cycle complete.',
                ['queue' => 'q1']
            );

        $iterator->notifyIdle();
        $iterator->next();
    }

    public function testNextLogsMessageReceivedReturningToHighPriorityQueue(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->setLogger($this->loggerMock);
        $iterator->rewind();

        // Advance past Q1 to Q2
        $iterator->notifyIdle();
        $iterator->next();

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'Message consumed from queue "{queue}"; returning to high-priority queue "{highQueue}".',
                ['queue' => 'q2', 'highQueue' => 'q1']
            );

        $iterator->notifyMessageReceived();
        $iterator->next();
    }

    public function testNextLogsQueueExhaustedSwitchingToNextWhenAtIndexGreaterThanZero(): void
    {
        $iterator = new HierarchicalStrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->setLogger($this->loggerMock);
        $iterator->rewind();

        // Advance to Q2
        $iterator->notifyIdle();
        $iterator->next();

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'Queue "{queue}" exhausted; switching to queue "{nextQueue}".',
                ['queue' => 'q2', 'nextQueue' => 'q3']
            );

        // Q2 idle, advance to Q3
        $iterator->notifyIdle();
        $iterator->next();
    }

    public function testNextLogsAllQueuesVisitedWhenHierarchicalCycleComplete(): void
    {
        $this->iterator->rewind();

        // Advance to Q2
        $this->iterator->notifyIdle();
        $this->iterator->next();

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with('All queues visited - hierarchical-strict-priority-interleaving cycle complete.');

        // Q2 idle, done
        $this->iterator->notifyIdle();
        $this->iterator->next();
    }
}
