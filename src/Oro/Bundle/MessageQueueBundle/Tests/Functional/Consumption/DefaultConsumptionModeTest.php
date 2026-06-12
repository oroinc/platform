<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityLowTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityMediumTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\QueueIterator\DefaultQueueIterator;

/**
 * Verifies that QueueConsumer works correctly in `default` consumption mode.
 *
 * Queue topology used in these tests:
 *   - HIGH   queue: oro.test.priority.high   (first bound  - Q1)
 *   - MEDIUM queue: oro.test.priority.medium (second bound - Q2)
 *   - LOW    queue: oro.test.priority.low    (third bound  - Q3)
 *
 * Default cycle schema (three queues):
 *   Q1(1), Q2(1), Q3(1), Q1(1), Q2(1), Q3(1), ...
 *
 * The iterator advances by exactly one position on every call to next(), wrapping
 * back to Q1 after Q3. Idle polls (empty queue slot) still count as an iteration
 * but do NOT produce an entry in getProcessedMessages(), so the processed-message
 * list shows only messages that were actually consumed.
 */
class DefaultConsumptionModeTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConsumptionModeTestTrait;

    #[\Override]
    protected function getConsumptionMode(): string
    {
        return DefaultQueueIterator::NAME;
    }

    // =========================================================================
    // Two-queue tests  (HIGH = Q1, MEDIUM = Q2)
    // =========================================================================

    /**
     * When only the HIGH queue has messages the consumer processes all of them.
     * The MEDIUM slots produce idle events that do not appear in the processed-message list.
     *
     *   Sent: H1, H2, H3  (MEDIUM empty)
     *   Cycle 1: Q1 -> H1 consumed; Q2 -> idle (no entry).
     *   Cycle 2: Q1 -> H2 consumed; Q2 -> idle.
     *   Cycle 3: Q1 -> H3 consumed; limit reached.
     *   Expected order: [HIGH, HIGH, HIGH]
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
            'All 3 messages should be consumed from the HIGH queue; MEDIUM idle slots are invisible.'
        );
    }

    /**
     * When only the MEDIUM queue has messages the consumer processes all of them.
     * The HIGH slots produce idle events that do not appear in the processed-message list.
     *
     *   Sent: M1, M2, M3  (HIGH empty)
     *   Cycle 1: Q1 -> idle; Q2 -> M1 consumed.
     *   Cycle 2: Q1 -> idle; Q2 -> M2 consumed.
     *   Cycle 3: Q1 -> idle; Q2 -> M3 consumed; limit reached.
     *   Expected order: [MEDIUM, MEDIUM, MEDIUM]
     */
    public function testOnlyMediumQueueMessagesConsumedWhenHighIsEmpty(): void
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
            'All 3 messages should be consumed from the MEDIUM queue; HIGH idle slots are invisible.'
        );
    }

    /**
     * With equal message counts in both queues the consumer works in a perfectly
     * alternating HIGH-MEDIUM-HIGH-MEDIUM sequence.
     *
     *   Sent: H1, H2, M1, M2
     *   Cycle 1: Q1 -> H1; Q2 -> M1.
     *   Cycle 2: Q1 -> H2; Q2 -> M2.
     *   Expected order: [HIGH, MEDIUM, HIGH, MEDIUM]
     */
    public function testEqualMessagesInBothQueuesProduceStrictlyAlternatingOrder(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');

        $this->consumeWith2Queues(4);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Equal counts must produce a perfect HIGH-MEDIUM alternation.'
        );
    }

    /**
     * When HIGH has more messages than MEDIUM they are interleaved: each cycle still
     * visits both queues in binding order; the MEDIUM slot becomes idle after its
     * single message is consumed.
     *
     *   Sent: H1, H2, H3, M1
     *   Cycle 1: Q1 -> H1; Q2 -> M1.
     *   Cycle 2: Q1 -> H2; Q2 -> idle.
     *   Cycle 3: Q1 -> H3; limit reached.
     *   Processed order: [HIGH, MEDIUM, HIGH, HIGH]
     *
     *   Positional invariants:
     *     HIGH positions[0] == 0  (HIGH is the first queue in binding order)
     *     HIGH positions[0] < MEDIUM positions[0]  (MEDIUM is never processed before HIGH)
     *     MEDIUM positions[0] < HIGH positions[2]  (MEDIUM is processed before the last HIGH)
     */
    public function testHighQueueWithMoreMessagesIsInterleavedWithMediumQueue(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 3, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');

        $this->consumeWith2Queues(4);

        $queueOrder = $this->getProcessedQueueOrder();
        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
            ],
            $queueOrder,
            'Expected: H1 then M1 in cycle 1, then H2 and H3 with MEDIUM idle slots in later cycles.'
        );

        $highPositions = $this->getQueuePositions($queueOrder, PriorityHighTestTopic::NAME);
        $medPositions = $this->getQueuePositions($queueOrder, PriorityMediumTestTopic::NAME);

        self::assertCount(3, $highPositions, '3 HIGH messages expected.');
        self::assertCount(1, $medPositions, '1 MEDIUM message expected.');

        self::assertSame(0, $highPositions[0], 'HIGH (Q1) must occupy the very first processed slot.');

        self::assertPositionBefore(
            $highPositions[0],
            $medPositions[0],
            'HIGH must be processed before MEDIUM in the first cycle.'
        );

        self::assertPositionBefore(
            $medPositions[0],
            $highPositions[2],
            'MEDIUM must be processed before the last HIGH message.'
        );
    }

    /**
     * When MEDIUM has more messages than HIGH the round-robin still interleaves normally;
     * the HIGH slot becomes idle after its single message is consumed.
     *
     *   Sent: H1, M1, M2, M3
     *   Cycle 1: Q1 -> H1; Q2 -> M1.
     *   Cycle 2: Q1 -> idle; Q2 -> M2.
     *   Cycle 3: Q1 -> idle; Q2 -> M3; limit reached.
     *   Expected order: [HIGH, MEDIUM, MEDIUM, MEDIUM]
     */
    public function testMediumQueueWithMoreMessagesIsInterleavedWithHighQueue(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');

        $this->consumeWith2Queues(4);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Expected: H1 first (cycle-1 Q1 slot), then M1-M3 occupying the remaining MEDIUM slots.'
        );
    }

    /**
     * Message arrival time must NOT affect consumption order: HIGH (Q1) leads in the first
     * default slot even when its message is sent after the MEDIUM message.
     *
     *   Sent (in order): M1, H1
     *   Cycle 1: Q1 -> H1; Q2 -> M1.
     *   Expected consumption order: H1 (position 0), M1 (position 1)
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
            'HIGH (Q1) must occupy the first consumption slot regardless of send order.'
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
     * Message arrival time must NOT affect consumption order: HIGH (Q1) leads in the first
     * consumption slot even when its message is sent after both MEDIUM and LOW messages.
     *
     *   Sent (in order): L1, M1, H1
     *   Cycle 1: Q1 -> H1; Q2 -> M1; Q3 -> L1.
     *   Expected: HIGH is at position 0.
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
            'HIGH (Q1) must occupy the first consumption slot regardless of send order.'
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
     * With equal message counts in all three queues the consumer works in a perfectly
     * ordered HIGH-MEDIUM-LOW-HIGH-MEDIUM-LOW sequence that repeats every cycle.
     *
     *   Sent: H1, H2, M1, M2, L1, L2
     *   Cycle 1: Q1 -> H1; Q2 -> M1; Q3 -> L1.
     *   Cycle 2: Q1 -> H2; Q2 -> M2; Q3 -> L2.
     *   Expected order: [HIGH, MEDIUM, LOW, HIGH, MEDIUM, LOW]
     */
    public function testThreeQueuesWithEqualMessagesRotateInBindingOrder(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(6);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Equal counts across three queues must produce a perfect HIGH-MEDIUM-LOW rotation.'
        );
    }

    /**
     * With exactly one message per queue the consumer visits each queue once in
     * binding order and the result is a single HIGH-MEDIUM-LOW cycle.
     *
     *   Sent: H1, M1, L1
     *   Cycle 1: Q1 -> H1; Q2 -> M1; Q3 -> L1.
     *   Expected order: [HIGH, MEDIUM, LOW]
     */
    public function testAllThreeQueuesAreProcessedWhenEachHasExactlyOneMessage(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 1, 'L');

        $this->consumeWith3Queues(3);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Single messages per queue must be consumed in binding order: HIGH, MEDIUM, LOW.'
        );
    }

    /**
     * HIGH and MEDIUM queues are empty; only LOW has messages.
     *
     *   Sent: L1, L2, L3  →  Each cycle hits Q1/Q2 idle slots, then Q3 consumes one LOW message.
     *   Expected order: [LOW, LOW, LOW]
     */
    public function testOnlyLowQueueMessagesConsumedWhenHighAndMediumAreEmpty(): void
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
            'All messages must be consumed from LOW; HIGH and MEDIUM idle slots are invisible.'
        );
    }

    /**
     * HIGH and LOW have messages; MEDIUM is empty - default round-robin interleaves by binding order.
     *
     *   Sent: H1, H2, L1, L2
     *   Cycle 1: Q1 H1; Q2 idle; Q3 L1.
     *   Cycle 2: Q1 H2; Q2 idle; Q3 L2.
     *   Expected order: [HIGH, LOW, HIGH, LOW]
     */
    public function testHighAndLowMessagesAreInterleavedWhenMediumQueueIsEmpty(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3Queues(4);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'With MEDIUM empty, HIGH and LOW alternate at their default slot positions each cycle.'
        );
    }

    /**
     * A queue with more messages than the others gets its extra messages in later cycles
     * but does not starve the remaining queues: MEDIUM and LOW still each get their
     * cycle-1 slot before HIGH receives its 4th message.
     *
     *   Sent: H1-H4, M1, L1
     *   Cycle 1: Q1 -> H1; Q2 -> M1; Q3 -> L1.
     *   Cycle 2: Q1 -> H2; Q2 -> idle; Q3 -> idle.
     *   Cycle 3: Q1 -> H3; Q2 -> idle; Q3 -> idle.
     *   Cycle 4: Q1 -> H4; limit reached.
     *   Processed order: [HIGH, MEDIUM, LOW, HIGH, HIGH, HIGH]
     *
     *   Positional invariants:
     *     HIGH positions[0] == 0  (HIGH is Q1 - first in binding order)
     *     MEDIUM positions[0] < HIGH positions[3]  (MEDIUM gets its slot before 4th HIGH)
     *     LOW positions[0]    < HIGH positions[3]  (LOW   gets its slot before 4th HIGH)
     */
    public function testQueueWithMoreMessagesThanOthersDoesNotStarveRemainingQueues(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 4, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 1, 'L');

        $this->consumeWith3Queues(6);

        $queueOrder = $this->getProcessedQueueOrder();
        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
            ],
            $queueOrder,
            'Cycle 1 must deliver H1, M1, L1; surplus HIGH messages fill later Q1 slots without starving MEDIUM/LOW.'
        );

        $highPositions = $this->getQueuePositions($queueOrder, PriorityHighTestTopic::NAME);
        $medPositions = $this->getQueuePositions($queueOrder, PriorityMediumTestTopic::NAME);
        $lowPositions = $this->getQueuePositions($queueOrder, PriorityLowTestTopic::NAME);

        self::assertCount(4, $highPositions, '4 HIGH messages expected.');
        self::assertCount(1, $medPositions, 'MEDIUM queue must not be starved; 1 message expected.');
        self::assertCount(1, $lowPositions, 'LOW queue must not be starved; 1 message expected.');

        self::assertSame(0, $highPositions[0], 'HIGH (Q1) must occupy the very first processed slot.');

        self::assertPositionBefore(
            $medPositions[0],
            $highPositions[3],
            'MEDIUM must get its cycle-1 slot before the 4th HIGH message is consumed.'
        );

        self::assertPositionBefore(
            $lowPositions[0],
            $highPositions[3],
            'LOW must get its cycle-1 slot before the 4th HIGH message is consumed.'
        );
    }

    /**
     * In a perfect default cycle with equal message counts (3x3), no queue should ever
     * receive two consecutive message slots. For every adjacent pair of positions
     * (i, i+1) in the processed-message list the queue names must differ.
     *
     *   Sent: H1-H3, M1-M3, L1-L3
     *   Expected order: [HIGH, MEDIUM, LOW, HIGH, MEDIUM, LOW, HIGH, MEDIUM, LOW]
     */
    public function testNoQueueReceivesTwoConsecutiveMessageSlotsBeforeOtherQueuesHadTheirTurn(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 3, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 3, 'L');

        $this->consumeWith3Queues(9);

        $queueOrder = $this->getProcessedQueueOrder();

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $queueOrder,
            'Each queue must be visited exactly once per cycle; no queue should appear at two consecutive positions.'
        );
    }
}
