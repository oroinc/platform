<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityLowTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityMediumTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\QueueIterator\StrictPriorityInterleavingQueueIterator;

/**
 * Verifies that QueueConsumer works correctly in `strict-priority-interleaving` mode.
 *
 * Queue topology used in these tests:
 *   - HIGH   queue: oro.test.priority.high   (first bound  – highest priority, Q1)
 *   - MEDIUM queue: oro.test.priority.medium (second bound – medium priority,  Q2)
 *   - LOW    queue: oro.test.priority.low    (third bound  – lowest priority,  Q3)
 *
 * Strict-priority-interleaving cycle schema (three queues):
 *   Q1(*), Q2(1), Q1(*), Q3(1)
 * Q1 is drained until idle, then one lower-priority queue is polled once per sub-cycle.
 */
class StrictPriorityInterleavingConsumptionModeTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConsumptionModeTestTrait;

    #[\Override]
    protected function getConsumptionMode(): string
    {
        return StrictPriorityInterleavingQueueIterator::NAME;
    }

    // =========================================================================
    // Two-queue tests  (HIGH = Q1, MEDIUM = Q2)
    // =========================================================================

    /**
     * When only the high-priority queue has messages the consumer drains it
     * and leaves the medium-priority queue untouched.
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
     * is consumed:
     *
     *   Sent: H1, H2, H3, M1  →  Expected order: H1, H2, H3, M1
     */
    public function testHighPriorityQueueIsDrainedBeforeMediumPriorityIsPolled(): void
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
     * Strict-priority-interleaving pattern over two full cycles (HIGH + MEDIUM):
     *
     *   Sent: H1, H2, M1, M2
     *   Cycle 1: HIGH drained (H1, H2), then MEDIUM polled once (M1).
     *   Cycle 2: HIGH idle immediately, then MEDIUM polled once (M2).
     */
    public function testInterleavingPatternAcrossTwoFullCyclesHighMedium(): void
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
            'Expected interleaving: H1, H2 (draining HIGH), then M1, M2 (MEDIUM each cycle).'
        );
    }

    /**
     * Invariant: the last HIGH position is always smaller than the first MEDIUM position,
     * regardless of the total message count.
     */
    public function testAllHighPriorityMessagesPrecedeAllMediumPriorityMessages(): void
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
            'Two-queue strict-priority: drain all HIGH in one Q1(*) phase, then one MEDIUM per completed cycle.'
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

    /**
     * Verifies that both queues are processed when each has exactly one message -
     * HIGH always leads because it is bound first (Q1).
     */
    public function testBothHighAndMediumQueuesAreProcessedWhenEachHasOneMessage(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');

        $this->consumeWith2Queues(2);

        $queueOrder = $this->getProcessedQueueOrder();

        self::assertCount(2, $queueOrder);
        self::assertSame(PriorityHighTestTopic::NAME, $queueOrder[0], 'HIGH message must be processed first.');
        self::assertSame(PriorityMediumTestTopic::NAME, $queueOrder[1]);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $queueOrder,
            'Explicit full-queue order when each queue has exactly one message.'
        );
    }

    // =========================================================================
    // Three-queue tests  (HIGH = Q1, MEDIUM = Q2, LOW = Q3)
    // =========================================================================

    /**
     * With three queues the strict-priority cycle drains HIGH before each lower-priority
     * poll, and Q2 is polled before Q3 within a super-cycle:
     *
     *   Cycle schema: Q1(*), Q2(1), Q1(*), Q3(1)
     *
     *   Sent: H1, H2, M1, L1
     *   Sub-cycle 1: Q1 drained (H1, H2), then Q2 polled once (M1).
     *   Sub-cycle 2: Q1 idle immediately, then Q3 polled once (L1).
     *   Expected order: H1, H2, M1, L1
     */
    public function testThreeQueuesStrictPriorityInterleavingPattern(): void
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
            'Expected: H1, H2 (HIGH drained), M1 (MEDIUM sub-cycle), L1 (LOW sub-cycle).'
        );
    }

    /**
     * With Q1 empty the cycle schema degenerates to a strict Q2–Q3 alternation:
     * one MEDIUM poll, then one LOW poll, then repeat.
     *
     *   Sent: M1, M2, L1, L2  →  Expected order: M1, L1, M2, L2
     */
    public function testMediumQueuePrecedesLowQueueInEachSubCycleWhenHighIsEmpty(): void
    {
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(4);

        self::assertSame(
            [
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'MEDIUM and LOW should strictly alternate M–L–M–L when HIGH is empty.'
        );
    }

    /**
     * HIGH and MEDIUM queues are empty; only LOW has messages.
     *
     *   Sent: L1, L2, L3
     *   Each strict-priority mini-cycle delivers one LOW after Q1/Q2 idle hops.
     *   Expected order: [LOW, LOW, LOW]
     */
    public function testOnlyLowQueueMessagesWhenHighAndMediumAreEmpty(): void
    {
        $this->sendMessages(PriorityLowTestTopic::getName(), 3, 'L');

        $this->consumeWith3Queues(3, 100000);

        self::assertSame(
            [
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'With Q1 and Q2 empty, Q3 receives one message per interleaving sub-cycle.'
        );
    }

    /**
     * HIGH and LOW have messages; MEDIUM is empty.
     *
     *   Sent: H1, H2, L1, L2  →  Drain HIGH, poll MEDIUM once (idle), then LOW; repeat.
     *   Expected order: [HIGH, HIGH, LOW, LOW]
     */
    public function testHighAndLowWhenMediumQueueIsEmpty(): void
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
            'All HIGH must be consumed before the first LOW in each high-drain phase; MEDIUM slots are idle.'
        );
    }

    /**
     * Full three-queue ordering invariants with 4×HIGH, 3×MEDIUM, 2×LOW:
     *
     *   Expected order: H1–H4 (HIGH drained), then M1, L1, M2, L2, M3
     *
     * Invariants checked:
     *   1. max(HIGH positions) < min(MEDIUM positions) - all HIGH before any MEDIUM
     *   2. max(HIGH positions) < min(LOW positions)    - all HIGH before any LOW
     *   3. For each LOW[i]: mediumPositions[i] < lowPositions[i]
     *      - within every sub-cycle pair, MEDIUM is polled before LOW
     */
    public function testThreeQueueFullOrderingInvariant(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 4, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(9);

        $queueOrder = $this->getProcessedQueueOrder();
        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $queueOrder,
            'Expected: H1–H4 (HIGH drained), then M1, L1, M2, L2, M3 per three-queue strict-priority schema.'
        );

        $highPos = $this->getQueuePositions($queueOrder, PriorityHighTestTopic::NAME);
        $mediumPos = $this->getQueuePositions($queueOrder, PriorityMediumTestTopic::NAME);
        $lowPos = $this->getQueuePositions($queueOrder, PriorityLowTestTopic::NAME);

        self::assertCount(4, $highPos, '4 high-priority messages expected.');
        self::assertCount(3, $mediumPos, '3 medium-priority messages expected.');
        self::assertCount(2, $lowPos, '2 low-priority messages expected.');

        // Invariant 1: all HIGH messages are consumed before any MEDIUM message.
        self::assertPositionBefore(
            max($highPos),
            $mediumPos[0],
            'All HIGH messages must precede all MEDIUM messages.'
        );

        // Invariant 2: all HIGH messages are consumed before any LOW message.
        self::assertPositionBefore(
            max($highPos),
            $lowPos[0],
            'All HIGH messages must precede all LOW messages.'
        );

        // Invariant 3: within every sub-cycle pair, MEDIUM is polled before LOW.
        foreach ($lowPos as $pairIndex => $lowPosition) {
            self::assertPositionBefore(
                $mediumPos[$pairIndex],
                $lowPosition,
                sprintf('MEDIUM[%d] must be consumed before LOW[%d] in the same sub-cycle.', $pairIndex, $pairIndex)
            );
        }
    }

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
     * Verifies that all three queues are eventually processed when each has exactly one message -
     * neither queue is starved by the strict-priority algorithm.
     */
    public function testAllThreeQueuesAreProcessedWhenEachHasOneMessage(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 1, 'L');

        $this->consumeWith3Queues(3);

        $queueOrder = $this->getProcessedQueueOrder();

        self::assertCount(3, $queueOrder);
        self::assertContains(PriorityHighTestTopic::NAME, $queueOrder, 'HIGH queue message should be processed.');
        self::assertContains(PriorityMediumTestTopic::NAME, $queueOrder, 'MEDIUM queue message should be processed.');
        self::assertContains(PriorityLowTestTopic::NAME, $queueOrder, 'LOW queue message should be processed.');
        // HIGH is Q1 - bound first - so it must be the very first message processed.
        self::assertSame(PriorityHighTestTopic::NAME, $queueOrder[0], 'HIGH must be processed first.');

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
}
