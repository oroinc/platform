<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityLowTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityMediumTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\QueueIterator\SequentialExhaustiveQueueIterator;

/**
 * Verifies that QueueConsumer works correctly in `sequential-exhaustive` mode.
 *
 * Queue topology used in these tests:
 *   - HIGH   queue: oro.test.priority.high   (first bound  – Q1)
 *   - MEDIUM queue: oro.test.priority.medium (second bound – Q2)
 *   - LOW    queue: oro.test.priority.low    (third bound  – Q3)
 *
 * Sequential-exhaustive algorithm:
 *   The iterator stays on the CURRENT queue until notifyIdle is called (queue is empty).
 *   It only advances to the next queue when the current queue is idle.
 *   After the last queue becomes idle, the cycle ends.
 *   Consumption schema: Q1 fully drained, then Q2 fully drained, then Q3 fully drained.
 *   No queue is ever revisited in the same cycle. Binding order = polling order.
 */
class SequentialExhaustiveConsumptionModeTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConsumptionModeTestTrait;

    #[\Override]
    protected function getConsumptionMode(): string
    {
        return SequentialExhaustiveQueueIterator::NAME;
    }

    // =========================================================================
    // Two-queue tests  (HIGH = Q1, MEDIUM = Q2)
    // =========================================================================

    /**
     * When only the high queue has messages the consumer drains it fully
     * and the medium queue is never polled.
     *
     *   Sent: H1, H2, H3, 0×MEDIUM  →  Expected order: [HIGH, HIGH, HIGH]
     */
    public function testOnlyHighQueueMessagesConsumedWhenMediumIsEmpty(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 3, 'H');

        $this->consumeWith2Queues(3);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'All 3 messages should be consumed from the high queue only.'
        );
    }

    /**
     * When Q1 (HIGH) is idle on the first poll the consumer advances to Q2 (MEDIUM) immediately
     * and drains it.
     *
     *   Sent: 0×HIGH, M1, M2, M3  →  Expected order: [MEDIUM, MEDIUM, MEDIUM]
     */
    public function testIfHighQueueIsEmptyMediumQueueIsPolledImmediately(): void
    {
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');

        $this->consumeWith2Queues(3);

        self::assertSame(
            [
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Q1 is idle immediately so all 3 messages should be consumed from the medium queue.'
        );
    }

    /**
     * Q1 (HIGH) is fully exhausted before Q2 (MEDIUM) receives a single poll.
     *
     *   Sent: H1, H2, H3, M1, M2  →  Expected order: [HIGH, HIGH, HIGH, MEDIUM, MEDIUM]
     */
    public function testHighQueueFullyExhaustedBeforeMediumIsPolled(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 3, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');

        $this->consumeWith2Queues(5);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'HIGH must be fully drained before any MEDIUM message is consumed.'
        );
    }

    /**
     * With exactly one message per queue the binding order determines consumption order.
     *
     *   Sent: H1, M1  →  Expected order: [HIGH, MEDIUM]
     */
    public function testSingleMessageInEachQueueConsumedInBindingOrder(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');

        $this->consumeWith2Queues(2);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'HIGH (Q1, bound first) must precede MEDIUM (Q2) when each has one message.'
        );
    }

    /**
     * Invariant: the last HIGH position is always smaller than the first MEDIUM position,
     * regardless of the total message count.
     *
     *   Sent: H1–H4, M1–M3  →  max(HIGH positions) < MEDIUM positions[0]
     */
    public function testAllHighMessagesPrecedeAllMediumMessages(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 4, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');

        $this->consumeWith2Queues(7);

        $queueOrder = $this->getProcessedQueueOrder();
        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $queueOrder,
            'Sequential-exhaustive: completely drain HIGH, then completely drain MEDIUM.'
        );

        $highPositions = $this->getQueuePositions($queueOrder, PriorityHighTestTopic::NAME);
        $medPositions = $this->getQueuePositions($queueOrder, PriorityMediumTestTopic::NAME);

        self::assertCount(4, $highPositions, '4 high-priority messages expected.');
        self::assertCount(3, $medPositions, '3 medium-priority messages expected.');
        self::assertPositionBefore(
            max($highPositions),
            $medPositions[0],
            'The last HIGH message must appear before the first MEDIUM message.'
        );
    }

    /**
     * Message arrival time must NOT affect consumption order: HIGH (Q1) is consumed first
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
            'HIGH (Q1) must be consumed first regardless of send order.'
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
     * Message arrival time must NOT affect consumption order: HIGH (Q1) is consumed first
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
            'HIGH (Q1) must be consumed first regardless of send order.'
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
     * KEY CORRECTNESS TEST: all three queues are fully drained in strict binding order
     * with absolutely no interleaving.
     *
     *   Sent: H1, H2, M1, M2, L1, L2  →  Expected order: [HIGH, HIGH, MEDIUM, MEDIUM, LOW, LOW]
     *   (This pattern would fail under strict-priority-interleaving.)
     */
    public function testThreeQueuesFullyExhaustedInStrictBindingOrder(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(6);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Sequential-exhaustive must fully drain each queue in binding order before moving to the next.'
        );
    }

    /**
     * When Q1 (HIGH) and Q2 (MEDIUM) are both idle the consumer advances directly to Q3 (LOW).
     *
     *   Sent: 0×HIGH, 0×MEDIUM, L1, L2, L3  →  Expected order: [LOW, LOW, LOW]
     */
    public function testIfFirstTwoQueuesAreEmptyThirdQueueIsPolledImmediately(): void
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
            'Q1 and Q2 are idle immediately so all messages must come from Q3 (LOW).'
        );
    }

    /**
     * HIGH and LOW have messages; MEDIUM is empty - sequential binding order still applies.
     *
     *   Sent: H1, H2, L1, L2  →  Q1 drained fully, then idle Q2 skipped, then Q3 drained.
     *   Expected order: [HIGH, HIGH, LOW, LOW]
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
            'HIGH must be fully exhausted before LOW is polled; empty MEDIUM is skipped in between.'
        );
    }

    /**
     * When Q1 (HIGH) is idle the consumer drains Q2 (MEDIUM) first, then Q3 (LOW).
     *
     *   Sent: 0×HIGH, M1, M2, L1, L2  →  Expected order: [MEDIUM, MEDIUM, LOW, LOW]
     */
    public function testIfFirstQueueIsEmptySecondAndThirdQueueRunInOrder(): void
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
            'Q1 is idle so Q2 must be fully drained before Q3 is polled.'
        );
    }

    /**
     * Mixed counts: Q1 drained (3), then Q2 drained (1), then Q3 drained (2).
     *
     *   Sent: H1, H2, H3, M1, L1, L2  →  Expected order: [HIGH, HIGH, HIGH, MEDIUM, LOW, LOW]
     */
    public function testHighDrainedThenMediumDrainedThenLow(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 3, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(6);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'HIGH must be fully drained, then MEDIUM, then LOW - no interleaving.'
        );
    }

    /**
     * Ordering invariants with three queues and larger message counts.
     *
     *   Sent: H1–H4, M1–M3, L1–L2
     *   Invariants:
     *     1. max(HIGH positions) < MEDIUM positions[0]  – all HIGH before any MEDIUM
     *     2. max(MEDIUM positions) < LOW positions[0]   – all MEDIUM before any LOW
     *     3. count(HIGH) = 4, count(MEDIUM) = 3, count(LOW) = 2
     */
    public function testOrderingInvariantsThreeQueues(): void
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
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $queueOrder,
            'Sequential-exhaustive: drain HIGH, then MEDIUM, then LOW.'
        );

        $highPos = $this->getQueuePositions($queueOrder, PriorityHighTestTopic::NAME);
        $mediumPos = $this->getQueuePositions($queueOrder, PriorityMediumTestTopic::NAME);
        $lowPos = $this->getQueuePositions($queueOrder, PriorityLowTestTopic::NAME);

        self::assertCount(4, $highPos, '4 high-priority messages expected.');
        self::assertCount(3, $mediumPos, '3 medium-priority messages expected.');
        self::assertCount(2, $lowPos, '2 low-priority messages expected.');

        self::assertPositionBefore(
            max($highPos),
            $mediumPos[0],
            'All HIGH messages must be consumed before any MEDIUM message.'
        );

        self::assertPositionBefore(
            max($mediumPos),
            $lowPos[0],
            'All MEDIUM messages must be consumed before any LOW message.'
        );
    }
}
