<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Iterates over bound queues using a strict-priority-interleaving algorithm.
 *
 * The first queue in `$boundQueues` is treated as the high-priority queue (Q1); all remaining
 * queues are lower-priority queues (Q2, Q3, ...).
 *
 * Consumption schema:
 *   - 1 queue:  Q1(*)
 *   - 2 queues: Q1(*), Q2(1)
 *   - 3 queues: Q1(*), Q2(1), Q1(*), Q3(1)
 *   - 4 queues: Q1(*), Q2(1), Q1(*), Q3(1), Q1(*), Q4(1)
 *   - 5 queues: Q1(*), Q2(1), Q1(*), Q3(1), Q1(*), Q4(1), Q1(*), Q5(1)
 *   - ... and so on.
 * The asterisk (*) means the iterator stays on Q1 until idle; "1" means
 * exactly one poll/consume of that lower-priority queue before returning to Q1.
 */
class StrictPriorityInterleavingQueueIterator implements NotifiableQueueIteratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string NAME = 'strict-priority-interleaving';

    /**
     * @var array<string>
     */
    private array $keys;

    /**
     * @var array<array<string,string>>
     */
    private array $values;

    private bool $isHighPriorityPhase = true;
    private int $currentLowerIndex = 0;
    private bool $lastPollHadMessage = false;
    private bool $isDone = false;

    /**
     * @param array<string,array<string,string>> $boundQueues Map of queue name => queue settings array,
     *                                                        ordered from highest to lowest priority.
     */
    public function __construct(array $boundQueues)
    {
        $this->keys = array_keys($boundQueues);
        $this->values = array_values($boundQueues);

        $this->logger = new NullLogger();
    }

    /**
     * Called by the consumption extension when the last polled queue yielded a message.
     * Causes the high-priority queue (Q1) to be re-polled on the next {@see next()} call
     * instead of advancing to the next lower-priority queue.
     */
    #[\Override]
    public function notifyMessageReceived(): void
    {
        $this->lastPollHadMessage = true;
    }

    /**
     * Called by the consumption extension when the last polled queue was empty.
     * Causes the iterator to advance to the next lower-priority queue (or end the cycle
     * when all lower-priority queues have been covered).
     */
    #[\Override]
    public function notifyIdle(): void
    {
        $this->lastPollHadMessage = false;
    }

    #[\Override]
    public function current(): array
    {
        if ($this->isHighPriorityPhase) {
            return $this->values[0];
        }

        return $this->values[$this->currentLowerIndex + 1];
    }

    #[\Override]
    public function key(): string
    {
        if ($this->isHighPriorityPhase) {
            return $this->keys[0];
        }

        return $this->keys[$this->currentLowerIndex + 1];
    }

    #[\Override]
    public function next(): void
    {
        if (!$this->valid()) {
            return;
        }

        $hadMessage = $this->lastPollHadMessage;
        $this->lastPollHadMessage = false;

        if ($this->isHighPriorityPhase) {
            if ($hadMessage) {
                $this->logger->debug(
                    'Continuing to drain high-priority queue "{queue}"; last poll had a message.',
                    ['queue' => $this->keys[0]]
                );

                return;
            }

            $lowerCount = count($this->keys) - 1;
            if ($this->currentLowerIndex >= $lowerCount) {
                $this->isDone = true;

                $this->logger->debug(
                    'High-priority queue "{queue}" exhausted; no lower-priority queues to visit - cycle complete.',
                    ['queue' => $this->keys[0]]
                );
            } else {
                $this->isHighPriorityPhase = false;

                $this->logger->debug(
                    'High-priority queue "{highQueue}" exhausted; switching to queue "{lowerQueue}".',
                    [
                        'highQueue' => $this->keys[0],
                        'lowerQueue' => $this->keys[$this->currentLowerIndex + 1],
                    ]
                );
            }
        } else {
            $this->currentLowerIndex++;
            $lowerCount = count($this->keys) - 1;
            if ($this->currentLowerIndex >= $lowerCount) {
                $this->isDone = true;

                $this->logger->debug('All queues visited - strict-priority-interleaving cycle complete.');
            } else {
                $this->isHighPriorityPhase = true;

                $this->logger->debug(
                    'Visited lower-priority queue "{lowerQueue}"; switching back to high-priority queue "{highQueue}".',
                    [
                        'lowerQueue' => $this->keys[$this->currentLowerIndex],
                        'highQueue' => $this->keys[0],
                    ]
                );
            }
        }
    }

    #[\Override]
    public function rewind(): void
    {
        if ($this->keys !== []) {
            $this->logger->debug(
                'Starting a new strict-priority-interleaving cycle; high-priority queue: "{queue}".',
                ['queue' => $this->keys[0]]
            );
        }

        $this->isHighPriorityPhase = true;
        $this->currentLowerIndex = 0;
        $this->lastPollHadMessage = false;
        $this->isDone = ($this->keys === []);
    }

    #[\Override]
    public function valid(): bool
    {
        if ($this->isDone) {
            return false;
        }

        if ($this->isHighPriorityPhase) {
            return isset($this->keys[0]);
        }

        return isset($this->keys[$this->currentLowerIndex + 1]);
    }
}
