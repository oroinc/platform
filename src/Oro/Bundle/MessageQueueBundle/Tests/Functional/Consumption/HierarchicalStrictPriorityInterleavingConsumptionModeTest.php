<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityLowTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityMediumTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\QueueIterator\HierarchicalStrictPriorityInterleavingQueueIterator;

/**
 * Verifies that QueueConsumer works correctly in `hierarchical-strict-priority-interleaving` mode.
 *
 * Queue topology used in these tests:
 *   - HIGH   queue: oro.test.priority.high   (first bound  – highest priority, Q1)
 *   - MEDIUM queue: oro.test.priority.medium (second bound – medium priority,  Q2)
 *   - LOW    queue: oro.test.priority.low    (third bound  – lowest priority,  Q3)
 *
 * Hierarchical-strict-priority-interleaving algorithm (three queues):
 *   - Poll Q1 until idle.
 *   - When Q1 idle: poll Q2 once; if Q2 yields a message → jump back to Q1 (restart Q1 drain).
 *   - When Q2 idle: poll Q3 once; if Q3 yields a message → jump back to Q1 (restart Q1 drain).
 *   - When Q3 idle: cycle ends.
 *
 * KEY DISTINCTION from strict-priority-interleaving:
 *   strict-priority-interleaving schema: Q1(*), Q2(1), Q1(*), Q3(1)
 *   hierarchical: Q2 is FULLY DRAINED before Q3 is ever polled.
 */
class HierarchicalStrictPriorityInterleavingConsumptionModeTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConsumptionModeTestTrait;

    #[\Override]
    protected function getConsumptionMode(): string
    {
        return HierarchicalStrictPriorityInterleavingQueueIterator::NAME;
    }

    // =========================================================================
    // Two-queue tests  (HIGH = Q1, MEDIUM = Q2)
    // =========================================================================

    /**
     * When only the high-priority queue has messages the consumer drains it
     * and leaves the medium-priority queue untouched.
     *
     *   Sent: H1, H2  →  Expected order: H1, H2
     */
    public function testOnlyHighPriorityMessagesAreConsumedWhenMediumQueueIsEmpty(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');

        $this->consumeWith2Queues(2);

        self::assertSame(
            [PriorityHighTestTopic::NAME, PriorityHighTestTopic::NAME],
            $this->getProcessedQueueOrder(),
            'Both messages should be consumed from the high-priority queue.'
        );
    }

    /**
     * When only the medium-priority queue has messages the consumer processes them normally.
     *
     *   Sent: M1, M2  →  Expected order: M1, M2
     */
    public function testOnlyMediumPriorityMessagesAreConsumedWhenHighQueueIsEmpty(): void
    {
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');

        $this->consumeWith2Queues(2);

        self::assertSame(
            [PriorityMediumTestTopic::NAME, PriorityMediumTestTopic::NAME],
            $this->getProcessedQueueOrder(),
            'Both messages should be consumed from the medium-priority queue.'
        );
    }

    /**
     * High-priority messages are fully drained before a single medium-priority message
     * is consumed. With only two queues, hierarchical behaves identically to
     * strict-priority-interleaving.
     *
     *   Sent: H1, H2, H3, M1  →  Expected order: H1, H2, H3, M1
     */
    public function testHighQueueIsDrainedBeforeMediumIsPolled(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 3, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');

        $this->consumeWith2Queues(4);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'All high-priority messages must be processed before the medium-priority message.'
        );
    }

    /**
     * With only two queues, hierarchical-strict-priority-interleaving is identical to
     * strict-priority-interleaving: HIGH is drained first, then MEDIUM messages follow.
     *
     *   Sent: H1, H2, M1, M2  →  Expected order: H1, H2, M1, M2
     */
    public function testHighAndMediumInterleavingIdenticalToStrictPriorityWith2Queues(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');

        $this->consumeWith2Queues(4);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'With two queues, the algorithms are identical: all HIGH then all MEDIUM.'
        );
    }

    /**
     * Invariant: the last HIGH position is always smaller than the first MEDIUM position,
     * regardless of the total message count.
     *
     *   Sent: 5 HIGH, 3 MEDIUM
     *   Assert: max(HIGH positions) < MEDIUM positions[0]
     */
    public function testAllHighMessagesPrecedeAllMediumMessages(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 5, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');

        $this->consumeWith2Queues(8);

        $queueOrder = $this->getProcessedQueueOrder();
        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $queueOrder,
            'Two-queue hierarchical matches strict-priority: all HIGH before each single MEDIUM slice per outer cycle.'
        );

        $highPositions = $this->getQueuePositions($queueOrder, PriorityHighTestTopic::NAME);
        $medPositions = $this->getQueuePositions($queueOrder, PriorityMediumTestTopic::NAME);

        self::assertCount(5, $highPositions, '5 high-priority messages expected.');
        self::assertCount(3, $medPositions, '3 medium-priority messages expected.');
        self::assertPositionBefore(
            max($highPositions),
            $medPositions[0],
            'The last HIGH message must appear before the first MEDIUM message.'
        );
    }

    /**
     * Message arrival time must NOT affect consumption order: HIGH is consumed first
     * even when its message is sent after the MEDIUM message.
     *
     *   Sent (in order): M1, H1  →  Expected consumption order: H1, M1
     */
    public function testHighQueueIsConsumedFirstEvenIfItsMessageIsSentAfterMedium(): void
    {
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');

        $this->consumeWith2Queues(2);

        $queueOrder = $this->getProcessedQueueOrder();

        self::assertSame(
            PriorityHighTestTopic::NAME,
            $queueOrder[0],
            'HIGH must be consumed first regardless of send order.'
        );
        self::assertSame(PriorityMediumTestTopic::NAME, $queueOrder[1]);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $queueOrder,
            'Explicit full-queue order: HIGH then MEDIUM.'
        );
    }

    // =========================================================================
    // Three-queue tests  (HIGH = Q1, MEDIUM = Q2, LOW = Q3)
    // =========================================================================

    /**
     * Message arrival time must NOT affect consumption order: HIGH is consumed first
     * even when its message is sent after both MEDIUM and LOW messages.
     *
     *   Sent (in order): L1, M1, H1  →  Expected consumption order: H1, M1, L1
     */
    public function testHighQueueIsConsumedFirstEvenIfItsMessageIsSentAfterMediumAndLow(): void
    {
        $this->sendMessages(PriorityLowTestTopic::getName(), 1, 'L');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');

        $this->consumeWith3Queues(3);

        $queueOrder = $this->getProcessedQueueOrder();

        self::assertSame(
            PriorityHighTestTopic::NAME,
            $queueOrder[0],
            'HIGH must be consumed first regardless of send order.'
        );

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $queueOrder,
            'Explicit full-queue order: HIGH, MEDIUM, LOW.'
        );
    }

    /**
     * KEY DISTINGUISHING TEST: With no HIGH messages, MEDIUM queue must be FULLY EXHAUSTED
     * before any LOW message is consumed.
     *
     * This is the critical difference from strict-priority-interleaving:
     *   strict-priority-interleaving: M1, L1, M2, L2  (alternating)
     *   hierarchical:                 M1, M2, L1, L2  (all MEDIUM before any LOW)
     *
     *   Sent: 0 HIGH, M1, M2, L1, L2  →  Expected order: M1, M2, L1, L2
     */
    public function testMediumQueueFullyExhaustedBeforeAnyLowMessageIsConsumed(): void
    {
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(4);

        self::assertSame(
            [
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'All MEDIUM messages must be consumed before any LOW message (hierarchical drain order).'
        );
    }

    /**
     * HIGH and MEDIUM queues are empty; only LOW has messages.
     *
     *   Sent: L1, L2, L3  →  Skip idle Q1/Q2, drain Q3 entirely.
     *   Expected order: [LOW, LOW, LOW]
     */
    public function testOnlyLowQueueMessagesWhenHighAndMediumAreEmpty(): void
    {
        $this->sendMessages(PriorityLowTestTopic::getName(), 3, 'L');

        $this->consumeWith3Queues(3);

        self::assertSame(
            [
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'When Q1/Q2 are idle at once, Q3 (LOW) is drained without interleaving noise.'
        );
    }

    /**
     * HIGH and LOW have messages; MEDIUM is empty - HIGH first, then LOW (no MEDIUM work).
     *
     *   Sent: H1, H2, L1, L2  →  Expected order: [HIGH, HIGH, LOW, LOW]
     */
    public function testHighThenLowWhenMediumQueueIsEmpty(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(4);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Hierarchical order: fully drain HIGH, skip empty MEDIUM drain, then drain LOW.'
        );
    }

    /**
     * HIGH messages are drained first, then MEDIUM fully, then LOW.
     *
     * After each MEDIUM or LOW message the algorithm re-checks Q1 (HIGH), but since HIGH
     * is already empty, Q2 (MEDIUM) is fully drained, and only then Q3 (LOW).
     *
     *   Sent: H1, H2, M1, L1  →  Expected order: H1, H2, M1, L1
     */
    public function testHighQueueRetakesControlAfterEachMediumOrLowMessage(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 1, 'L');

        $this->consumeWith3Queues(4);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Expected: all HIGH consumed first, then MEDIUM, then LOW.'
        );
    }

    /**
     * Full three-queue ordering invariants with 3×HIGH, 2×MEDIUM, 2×LOW:
     *
     *   Invariants checked:
     *   1. max(HIGH positions) < MEDIUM positions[0]   – all HIGH before any MEDIUM
     *   2. max(MEDIUM positions) < LOW positions[0]    – all MEDIUM before any LOW
     *   3. count(HIGH) = 3, count(MEDIUM) = 2, count(LOW) = 2
     */
    public function testAllHighMessagesPrecedeAllMediumAndAllMediumPrecedeAllLow(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 3, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(7);

        $queueOrder = $this->getProcessedQueueOrder();
        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $queueOrder,
            'Hierarchical three-queue: exhaust HIGH, then MEDIUM, then LOW.'
        );

        $highPos = $this->getQueuePositions($queueOrder, PriorityHighTestTopic::NAME);
        $mediumPos = $this->getQueuePositions($queueOrder, PriorityMediumTestTopic::NAME);
        $lowPos = $this->getQueuePositions($queueOrder, PriorityLowTestTopic::NAME);

        self::assertCount(3, $highPos, '3 high-priority messages expected.');
        self::assertCount(2, $mediumPos, '2 medium-priority messages expected.');
        self::assertCount(2, $lowPos, '2 low-priority messages expected.');

        // Invariant 1: all HIGH messages are consumed before any MEDIUM message.
        self::assertPositionBefore(
            max($highPos),
            $mediumPos[0],
            'All HIGH messages must precede all MEDIUM messages.'
        );

        // Invariant 2: all MEDIUM messages are consumed before any LOW message.
        self::assertPositionBefore(
            max($mediumPos),
            $lowPos[0],
            'All MEDIUM messages must precede all LOW messages (hierarchical drain order).'
        );
    }

    /**
     * Verifies that all three queues are eventually processed when each has exactly one message -
     * HIGH first (Q1), then MEDIUM (Q2), then LOW (Q3).
     *
     *   Sent: H1, M1, L1  →  Expected order: H1, M1, L1
     */
    public function testAllThreeQueuesAreProcessedWhenEachHasExactlyOneMessage(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 1, 'L');

        $this->consumeWith3Queues(3);

        $queueOrder = $this->getProcessedQueueOrder();

        self::assertCount(3, $queueOrder);
        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $queueOrder,
            'With one message each, expected exact order: HIGH, MEDIUM, LOW.'
        );
    }
}
