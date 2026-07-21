<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\QueueIterator\QueueIteratorInterface;
use Oro\Component\MessageQueue\Consumption\QueueIterator\StrictPriorityInterleavingQueueIterator;
use PHPUnit\Framework\TestCase;

final class StrictPriorityInterleavingQueueIteratorTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private StrictPriorityInterleavingQueueIterator $iterator;

    #[\Override]
    protected function setUp(): void
    {
        $this->iterator = new StrictPriorityInterleavingQueueIterator(
            ['q1' => [QueueConsumer::PROCESSOR => 'proc1'], 'q2' => [QueueConsumer::PROCESSOR => 'proc2']]
        );
        $this->setUpLoggerMock($this->iterator);
    }

    public function testImplementsQueueIteratorInterface(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator(['q1' => [QueueConsumer::PROCESSOR => 'proc1']]);

        self::assertInstanceOf(QueueIteratorInterface::class, $iterator);
    }

    public function testSingleQueueCycleEndsAfterOneIdlePoll(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator(['q1' => [QueueConsumer::PROCESSOR => 'proc1']]);
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());

        $iterator->notifyIdle();
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testSingleQueueRewindRestartsCycle(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator(['q1' => [QueueConsumer::PROCESSOR => 'p1']]);
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

    public function testFullCycleWithThreeQueuesNoMessages(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator([
            'q1' => [QueueConsumer::PROCESSOR => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->rewind();

        // Step 1: high-priority phase
        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());

        // Q1 is idle, switch to lower-priority queue q2
        $iterator->notifyIdle();
        $iterator->next();

        // Step 2: lower-priority phase
        self::assertTrue($iterator->valid());
        self::assertSame('q2', $iterator->key());

        // After q2, switch back to high-priority q1
        $iterator->next();

        // Step 3: high-priority phase
        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());

        // Q1 is idle, advance to next lower-priority queue q3
        $iterator->notifyIdle();
        $iterator->next();

        // Step 4: lower-priority phase
        self::assertTrue($iterator->valid());
        self::assertSame('q3', $iterator->key());

        // After q3 - all lower-priority queues visited
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testHighPriorityQueueDrainsBeforeMovingToLower(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->rewind();

        // Step 1: q1 - message received, stay
        self::assertSame('q1', $iterator->key());
        $iterator->notifyMessageReceived();
        $iterator->next();

        // Step 2: q1 - message received, stay
        self::assertSame('q1', $iterator->key());
        $iterator->notifyMessageReceived();
        $iterator->next();

        // Step 3: q1 - idle, switch to q2
        self::assertSame('q1', $iterator->key());
        $iterator->notifyIdle();
        $iterator->next();

        // Step 4: q2 - lower-priority poll, then back to q1
        self::assertSame('q2', $iterator->key());
        $iterator->next();

        // Step 5: q1 - idle, switch to q3
        self::assertSame('q1', $iterator->key());
        $iterator->notifyIdle();
        $iterator->next();

        // Step 6: q3 - last lower-priority queue
        self::assertSame('q3', $iterator->key());
        $iterator->next();

        self::assertFalse($iterator->valid());
    }

    public function testLowerPriorityPhaseIgnoresNotifyMessageReceived(): void
    {
        // notifyMessageReceived in the lower-priority phase should NOT keep us on q2;
        // the iterator always switches back to the high-priority queue after one lower-priority poll/consume.
        $iterator = new StrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->rewind();

        // Transition from q1 (idle) to q2 (lower-priority phase)
        $iterator->notifyIdle();
        $iterator->next();

        self::assertSame('q2', $iterator->key());

        // Even though a message was received on q2, next() should switch back to q1
        $iterator->notifyMessageReceived();
        $iterator->next();

        self::assertSame('q1', $iterator->key());
        self::assertTrue($iterator->valid());
    }

    public function testRewindResetsAllStateForFreshCycle(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'p1'],
            'q2' => ['processor' => 'p2'],
        ]);
        $iterator->rewind();

        // Complete the full cycle
        $iterator->notifyIdle();
        $iterator->next(); // switch to q2
        $iterator->next(); // done

        self::assertFalse($iterator->valid());

        // Rewind should fully reset state
        $iterator->rewind();

        self::assertTrue($iterator->valid());
        self::assertSame('q1', $iterator->key());
        self::assertSame(['processor' => 'p1'], $iterator->current());
    }

    public function testKeyAndCurrentReturnCorrectValuesInHighAndLowerPhase(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator([
            'high-queue' => ['processor' => 'high-proc'],
            'lower-queue' => ['processor' => 'lower-proc'],
        ]);
        $iterator->rewind();

        // High-priority phase
        self::assertSame('high-queue', $iterator->key());
        self::assertSame(['processor' => 'high-proc'], $iterator->current());

        // Transition to lower-priority phase
        $iterator->notifyIdle();
        $iterator->next();

        // Lower-priority phase
        self::assertSame('lower-queue', $iterator->key());
        self::assertSame(['processor' => 'lower-proc'], $iterator->current());
    }

    public function testIsValidWhenEmptyBoundQueues(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator([]);
        $iterator->rewind();

        self::assertFalse($iterator->valid());
    }

    public function testRewindLogsStartingNewCycle(): void
    {
        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'Starting a new strict-priority-interleaving cycle; high-priority queue: "{queue}".',
                ['queue' => 'q1']
            );

        $this->iterator->rewind();
    }

    public function testRewindDoesNotLogWhenNoQueues(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator([]);
        $iterator->setLogger($this->loggerMock);

        $this->loggerMock->expects(self::never())
            ->method('debug');

        $iterator->rewind();
    }

    public function testNextLogsDrainingHighPriorityQueueWhenMessageReceived(): void
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

    public function testNextLogsSwitchingToLowerPriorityQueue(): void
    {
        $this->iterator->rewind();

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'High-priority queue "{highQueue}" exhausted; switching to queue "{lowerQueue}".',
                ['highQueue' => 'q1', 'lowerQueue' => 'q2']
            );

        $this->iterator->notifyIdle();
        $this->iterator->next();
    }

    public function testNextLogsAllQueuesVisitedWhenCycleComplete(): void
    {
        $this->iterator->rewind();
        $this->iterator->notifyIdle();
        $this->iterator->next(); // Switching to q2 (lower-priority phase)

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with('All queues visited - strict-priority-interleaving cycle complete.');

        $this->iterator->next(); // Done
    }

    public function testNextLogsSwitchingBackToHighPriorityQueue(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator([
            'q1' => ['processor' => 'proc1'],
            'q2' => ['processor' => 'proc2'],
            'q3' => ['processor' => 'proc3'],
        ]);
        $iterator->setLogger($this->loggerMock);
        $iterator->rewind();

        $iterator->notifyIdle();
        $iterator->next(); // Switching to q2 (lower-priority phase)

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'Visited lower-priority queue "{lowerQueue}"; switching back to high-priority queue "{highQueue}".',
                ['lowerQueue' => 'q2', 'highQueue' => 'q1']
            );

        $iterator->next(); // Back to high q1
    }

    public function testNextLogsCycleCompleteWhenSingleQueueExhausted(): void
    {
        $iterator = new StrictPriorityInterleavingQueueIterator(['q1' => ['processor' => 'proc1']]);
        $iterator->setLogger($this->loggerMock);
        $iterator->rewind();

        $this->loggerMock->expects(self::once())
            ->method('debug')
            ->with(
                'High-priority queue "{queue}" exhausted; no lower-priority queues to visit - cycle complete.',
                ['queue' => 'q1']
            );

        $iterator->notifyIdle();
        $iterator->next();
    }
}
