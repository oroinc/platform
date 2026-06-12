<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityHighTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityLowTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\Async\Topic\PriorityMediumTestTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\QueueIterator\WeightedRoundRobinQueueIterator;

/**
 * Verifies that QueueConsumer works correctly in `weighted-round-robin` mode.
 *
 * Queue topology used in these tests:
 *   - HIGH   queue: oro.test.priority.high   (first bound  – Q1)
 *   - MEDIUM queue: oro.test.priority.medium (second bound – Q2)
 *   - LOW    queue: oro.test.priority.low    (third bound  – Q3)
 *
 * Weighted-round-robin algorithm:
 *   Each queue is consumed for up to `weight` messages per cycle slot.
 *   When a queue is idle the iterator advances to the next queue immediately,
 *   regardless of the remaining weight budget.
 *   After the last queue in the cycle has been visited, foreach ends and
 *   the outer while(true) restarts with a fresh rewind.
 *   Default weight is 1.
 *
 *   Consumption schema for HIGH(w=2), MEDIUM(w=1):
 *     cycle 1:  Q1(H1), Q1(H2), Q2(M1)
 *     cycle 2:  Q1(H3), Q1(H4), Q2(M2)
 *     ...
 */
class WeightedRoundRobinConsumptionModeTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConsumptionModeTestTrait;

    #[\Override]
    protected function getConsumptionMode(): string
    {
        return WeightedRoundRobinQueueIterator::NAME;
    }

    // =========================================================================
    // Default weight (= 1) tests
    // =========================================================================

    /**
     * When no weight is specified it defaults to 1, producing strict alternation
     * between queues.
     *
     *   Sent: H1, H2, M1, M2  →  Expected order: [HIGH, MEDIUM, HIGH, MEDIUM]
     */
    public function testDefaultWeightOfOneAlternatesQueuesLikeRoundRobin(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');

        // bindQueue without weight → defaults to 1
        $this->consumeWith2Queues(4);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Default weight=1 must alternate queues exactly like in default consumption mode.'
        );
    }

    // =========================================================================
    // Two-queue weighted tests  (HIGH = Q1, MEDIUM = Q2)
    // =========================================================================

    /**
     * HIGH(w=2), MEDIUM(w=1): two messages are consumed from HIGH before one from MEDIUM per cycle.
     *
     *   Sent: H1, H2, H3, M1, M2
     *   Cycle 1: H1, H2, M1
     *   Cycle 2: H3, M2
     *   Expected order: [HIGH, HIGH, MEDIUM, HIGH, MEDIUM]
     */
    public function testHighWeight2MediumWeight1ProducesTwoHighThenOneMediumPerCycle(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 3, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');

        $this->consumeWith2WeightedQueues(2, 1, 5);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'HIGH(w=2) must yield two messages before MEDIUM(w=1) yields one, per cycle.'
        );
    }

    /**
     * HIGH(w=1), MEDIUM(w=2): one message from HIGH then two messages from MEDIUM per cycle.
     *
     *   Sent: H1, H2, M1, M2, M3
     *   Cycle 1: H1, M1, M2
     *   Cycle 2: H2, M3
     *   Expected order: [HIGH, MEDIUM, MEDIUM, HIGH, MEDIUM]
     */
    public function testHighWeight1MediumWeight2ProducesOneHighThenTwoMediumPerCycle(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');

        $this->consumeWith2WeightedQueues(1, 2, 5);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'HIGH(w=1) must yield one message before MEDIUM(w=2) yields two, per cycle.'
        );
    }

    /**
     * HIGH(w=3), MEDIUM(w=1): three messages from HIGH before one from MEDIUM per cycle.
     *
     *   Sent: H1, H2, H3, H4, M1, M2
     *   Cycle 1: H1, H2, H3, M1
     *   Cycle 2: H4, M2
     *   Expected order: [HIGH, HIGH, HIGH, MEDIUM, HIGH, MEDIUM]
     */
    public function testHighWeight3MediumWeight1ProducesThreeHighThenOneMediumPerCycle(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 4, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');

        $this->consumeWith2WeightedQueues(3, 1, 6);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'HIGH(w=3) must yield three messages before MEDIUM(w=1) yields one, per cycle.'
        );
    }

    /**
     * When HIGH (w=5) is idle the iterator advances to MEDIUM immediately,
     * without waiting for the full weight budget to be exhausted.
     *
     *   Sent: 0×HIGH, M1, M2, M3
     *   Cycle 1: HIGH idle → advance; MEDIUM(w=1): M1
     *   Cycle 2: HIGH idle → advance; MEDIUM(w=1): M2
     *   Cycle 3: HIGH idle → advance; MEDIUM(w=1): M3
     *   Expected order: [MEDIUM, MEDIUM, MEDIUM]
     */
    public function testIdleOnHighAdvancesToMediumImmediatelyRegardlessOfWeight(): void
    {
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');

        $this->consumeWith2WeightedQueues(5, 1, 3);

        self::assertSame(
            [
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'An idle poll on HIGH(w=5) must advance immediately to MEDIUM regardless of remaining budget.'
        );
    }

    /**
     * When MEDIUM (w=5) is partially consumed then goes idle the iterator advances
     * to the end of the cycle, and the next cycle restarts from HIGH.
     *
     *   HIGH(w=1), MEDIUM(w=5)
     *   Sent: H1, M1         (MEDIUM gets idle after M1, before its budget of 5 is reached)
     *   Cycle 1: H1, M1 (MEDIUM goes idle after 1 msg → advance → cycle ends)
     *   Expected order: [HIGH, MEDIUM]
     */
    public function testIdleOnMediumBeforeWeightBudgetAdvancesImmediately(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 1, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 1, 'M');

        $this->consumeWith2WeightedQueues(1, 5, 2);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'MEDIUM going idle before its weight budget is reached must still advance immediately.'
        );
    }

    // =========================================================================
    // Three-queue weighted tests  (HIGH = Q1, MEDIUM = Q2, LOW = Q3)
    // =========================================================================

    /**
     * HIGH(w=2), MEDIUM(w=2), LOW(w=1): two from HIGH, two from MEDIUM, one from LOW per cycle.
     *
     *   Sent: H1, H2, M1, M2, L1
     *   Cycle 1: H1, H2, M1, M2, L1  →  all consumed
     *   Expected order: [HIGH, HIGH, MEDIUM, MEDIUM, LOW]
     */
    public function testThreeQueuesWithWeights221ProducesCorrectPattern(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 2, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 2, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 1, 'L');

        $this->consumeWith3WeightedQueues(2, 2, 1, 5);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'With weights [2,2,1] the pattern must be H,H,M,M,L within one cycle.'
        );
    }

    /**
     * HIGH(w=3), MEDIUM(w=2), LOW(w=1), spanning two cycles.
     *
     *   Sent: H1–H4, M1–M3, L1–L2
     *   Cycle 1: H1, H2, H3, M1, M2, L1
     *   Cycle 2: H4, M3, L2
     *   Expected order: [HIGH, HIGH, HIGH, MEDIUM, MEDIUM, LOW, HIGH, MEDIUM, LOW]
     */
    public function testThreeQueuesWithWeights321SpanningTwoCycles(): void
    {
        $this->sendMessages(PriorityHighTestTopic::getName(), 4, 'H');
        $this->sendMessages(PriorityMediumTestTopic::getName(), 3, 'M');
        $this->sendMessages(PriorityLowTestTopic::getName(), 2, 'L');

        $this->consumeWith3WeightedQueues(3, 2, 1, 9);

        self::assertSame(
            [
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityHighTestTopic::NAME,
                PriorityMediumTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'With weights [3,2,1] the 9 messages must be consumed in two cycles: 3+2+1 then 1+1+1.'
        );
    }

    /**
     * Default weight (=1) on all three queues behaves like default round-robin when only LOW has work.
     *
     *   Sent: L1, L2, L3 (HIGH and MEDIUM empty)
     *   Expected order: [LOW, LOW, LOW]
     */
    public function testOnlyLowWhenHighAndMediumEmptyWithDefaultWeights(): void
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
            'Idle HIGH/MEDIUM slots must advance immediately when weight=1; all messages come from LOW.'
        );
    }

    /**
     * Default weight (=1): HIGH and LOW have messages; MEDIUM is empty.
     *
     *   Sent: H1, H2, L1, L2  →  Expected order: [HIGH, LOW, HIGH, LOW]
     */
    public function testHighAndLowInterleavedWhenMediumEmptyDefaultWeights(): void
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
            'With uniform weight=1 and empty MEDIUM, HIGH and LOW alternate like default mode.'
        );
    }

    /**
     * When the first two queues are idle (w=3 for both, but empty) the iterator advances
     * directly to LOW and drains it.
     *
     *   Sent: 0×HIGH, 0×MEDIUM, L1, L2, L3
     *   Expected order: [LOW, LOW, LOW]
     */
    public function testIfFirstTwoWeightedQueuesAreEmptyThirdQueueIsPolledImmediately(): void
    {
        $this->sendMessages(PriorityLowTestTopic::getName(), 3, 'L');

        $this->consumeWith3WeightedQueues(3, 3, 1, 3);

        self::assertSame(
            [
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
                PriorityLowTestTopic::NAME,
            ],
            $this->getProcessedQueueOrder(),
            'Idle on HIGH(w=3) and MEDIUM(w=3) must advance immediately; all messages come from LOW.'
        );
    }
}
