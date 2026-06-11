<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Iterates over bound queues in sequential-exhaustive order.
 *
 * The first queue in `$boundQueues` is fully drained before the iterator advances to the next;
 * after the last queue is exhausted the cycle ends.
 *
 * Consumption schema:
 *   - 1 queue:  Q1(*)
 *   - 2 queues: Q1(*), Q2(*)
 *   - 3 queues: Q1(*), Q2(*), Q3(*)
 *   - 4 queues: Q1(*), Q2(*), Q3(*), Q4(*)
 *   - 5 queues: Q1(*), Q2(*), Q3(*), Q4(*), Q5(*)
 *   - ... and so on.
 * The asterisk (*) means the iterator stays on the current queue until idle;
 * it then advances to the next queue in sequence.
 */
class SequentialExhaustiveQueueIterator implements NotifiableQueueIteratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string NAME = 'sequential-exhaustive';

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
     * @param array<string,array<string,string>> $boundQueues Map of queue name => queue settings array.
     */
    public function __construct(array $boundQueues)
    {
        $this->keys = array_keys($boundQueues);
        $this->values = array_values($boundQueues);

        $this->logger = new NullLogger();
    }

    /**
     * Called by the consumption extension when the last polled queue yielded a message.
     * Causes the iterator to stay on the current queue on the next {@see next()} call
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
     * when all queues have been exhausted).
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
    public function valid(): bool
    {
        return !$this->isDone;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->currentIndex = 0;
        $this->lastPollHadMessage = false;
        $this->isDone = ($this->keys === []);

        if ($this->keys !== []) {
            $this->logger->debug(
                'Starting a new sequential-exhaustive cycle; first queue: "{queue}".',
                ['queue' => $this->keys[0]]
            );
        }
    }

    #[\Override]
    public function next(): void
    {
        if (!$this->valid()) {
            return;
        }

        $hadMessage = $this->lastPollHadMessage;
        $this->lastPollHadMessage = false;

        if ($hadMessage) {
            $this->logger->debug(
                'Continuing to drain queue "{queue}"; last poll had a message.',
                ['queue' => $this->keys[$this->currentIndex]]
            );

            return;
        }

        if (isset($this->keys[$this->currentIndex + 1])) {
            $prevQueue = $this->keys[$this->currentIndex];
            $this->currentIndex++;
            $this->logger->debug(
                'Queue "{prevQueue}" exhausted; switching to "{nextQueue}".',
                ['prevQueue' => $prevQueue, 'nextQueue' => $this->keys[$this->currentIndex]]
            );
        } else {
            $this->isDone = true;
            $this->logger->debug('All queues exhausted - sequential-exhaustive cycle complete.');
        }
    }
}
