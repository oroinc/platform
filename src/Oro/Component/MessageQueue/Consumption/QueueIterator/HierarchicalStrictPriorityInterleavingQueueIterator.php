<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Iterates over bound queues using a fully recursive hierarchical-strict-priority-interleaving algorithm.
 *
 * The first queue in `$boundQueues` is treated as the high-priority queue (Q1); all remaining
 * queues are lower-priority queues (Q2, Q3, ...).
 *
 * Consumption schema:
 *   - 1 queue:  Q1(*)
 *   - 2 queues: Q1(*), Q2(1)
 *   - 3 queues: ( Q1(*), Q2(1) )(*), Q3(1)
 *   - 4 queues: ( ( Q1(*), Q2(1) )(*), Q3(1) )(*), Q4(1)
 *   - 5 queues: ( ( ( Q1(*), Q2(1) )(*), Q3(1) )(*), Q4(1) )(*), Q5(1)
 *   - ... and so on.
 * The asterisk (*) means the inner sub-pattern is repeated until idle; "1" means exactly one poll/consume
 * of that queue before restarting the sub-pattern from Q1.
 */
class HierarchicalStrictPriorityInterleavingQueueIterator implements
    NotifiableQueueIteratorInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string NAME = 'hierarchical-strict-priority-interleaving';

    /**
     * @var array<string>
     */
    private array $keys;

    /**
     * @var array<array<string,string>>
     */
    private array $values;

    private int $currentIndex = 0;
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
     * Causes the iterator to return to the high-priority queue (Q1) on the next {@see next()} call
     * instead of advancing to the next queue in the sequence.
     */
    #[\Override]
    public function notifyMessageReceived(): void
    {
        $this->lastPollHadMessage = true;
    }

    /**
     * Called by the consumption extension when the last polled queue was empty.
     * Causes the iterator to advance to the next queue in the sequence (or end the cycle
     * when all queues have been covered).
     */
    #[\Override]
    public function notifyIdle(): void
    {
        $this->lastPollHadMessage = false;
    }

    #[\Override]
    public function current(): array
    {
        return $this->values[$this->currentIndex];
    }

    #[\Override]
    public function key(): string
    {
        return $this->keys[$this->currentIndex];
    }

    #[\Override]
    public function next(): void
    {
        if (!$this->valid()) {
            return;
        }

        $hadMessage = $this->lastPollHadMessage;
        $this->lastPollHadMessage = false;

        if ($this->currentIndex === 0) {
            if ($hadMessage) {
                $this->logger->debug(
                    'Continuing to drain high-priority queue "{queue}"; last poll had a message.',
                    ['queue' => $this->keys[0]]
                );
            } elseif (isset($this->keys[1])) {
                $this->currentIndex = 1;
                $this->logger->debug(
                    'High-priority queue "{queue}" exhausted; switching to queue "{nextQueue}".',
                    ['queue' => $this->keys[0], 'nextQueue' => $this->keys[1]]
                );
            } else {
                $this->isDone = true;
                $this->logger->debug(
                    'High-priority queue "{queue}" exhausted; no further queues - cycle complete.',
                    ['queue' => $this->keys[0]]
                );
            }
        } else {
            if ($hadMessage) {
                $currentQueue = $this->keys[$this->currentIndex];
                $this->currentIndex = 0;
                $this->logger->debug(
                    'Message consumed from queue "{queue}"; returning to high-priority queue "{highQueue}".',
                    ['queue' => $currentQueue, 'highQueue' => $this->keys[0]]
                );
            } elseif (isset($this->keys[$this->currentIndex + 1])) {
                $this->currentIndex++;
                $this->logger->debug(
                    'Queue "{queue}" exhausted; switching to queue "{nextQueue}".',
                    [
                        'queue' => $this->keys[$this->currentIndex - 1],
                        'nextQueue' => $this->keys[$this->currentIndex],
                    ]
                );
            } else {
                $this->isDone = true;
                $this->logger->debug('All queues visited - hierarchical-strict-priority-interleaving cycle complete.');
            }
        }
    }

    #[\Override]
    public function rewind(): void
    {
        $this->currentIndex = 0;
        $this->lastPollHadMessage = false;
        $this->isDone = ($this->keys === []);

        if ($this->keys !== []) {
            $this->logger->debug(
                'Starting a new hierarchical-strict-priority-interleaving cycle; '
                . 'high-priority queue: "{queue}".',
                ['queue' => $this->keys[0]]
            );
        }
    }

    #[\Override]
    public function valid(): bool
    {
        return !$this->isDone;
    }
}
