<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Iterates over bound queues in weighted-round-robin order.
 *
 * Each queue is consumed for up to `weight` messages before the iterator advances to the next queue.
 * When a queue is idle (no message received) the iterator advances immediately, regardless of the
 * remaining weight budget. After the last queue has been visited the cycle ends.
 * Queues that do not specify a weight setting default to weight 1.
 *
 * Consumption schema (w1, w2 are the configured weights):
 *   - 1 queue:  Q1(w1)
 *   - 2 queues: Q1(w1), Q2(w2)
 *   - 3 queues: Q1(w1), Q2(w2), Q3(w3)
 *   - ... and so on.
 * Each queue is consumed at most `weight` times before the iterator moves on;
 * an idle poll advances the iterator immediately to the next queue.
 */
class WeightedRoundRobinQueueIterator implements NotifiableQueueIteratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string NAME = 'weighted-round-robin';
    public const string WEIGHT = 'weight';

    /**
     * @var array<string>
     */
    private array $keys;

    /**
     * @var array<array<string,string>>
     */
    private array $values;

    /**
     * @var array<int,int>
     */
    private array $weights;

    private int $currentIndex = 0;
    private int $currentMessageCount = 0;
    private bool $lastPollHadMessage = false;
    private bool $isDone = false;

    /**
     * @param array<string,array{processor: string, weight: string}> $boundQueues Map of queue name => queue settings
     *                                                                            array.
     */
    public function __construct(array $boundQueues)
    {
        $this->keys = array_keys($boundQueues);
        $this->values = array_values($boundQueues);

        $this->weights = [];
        foreach ($this->values as $position => $settings) {
            $this->weights[$position] = max(1, (int)($settings[self::WEIGHT] ?? 1));
        }

        $this->logger = new NullLogger();
    }

    #[\Override]
    public function notifyMessageReceived(): void
    {
        $this->lastPollHadMessage = true;
    }

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
        $this->currentMessageCount = 0;
        $this->lastPollHadMessage = false;
        $this->isDone = ($this->keys === []);

        if ($this->keys !== []) {
            $this->logger->debug(
                'Starting a new weighted-round-robin cycle; first queue: "{queue}" (weight: {weight}).',
                ['queue' => $this->keys[0], 'weight' => $this->weights[0]]
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
            $this->currentMessageCount++;

            if ($this->currentMessageCount >= $this->weights[$this->currentIndex]) {
                $this->advance(true);
            } else {
                $this->logger->debug(
                    'Consumed message {count}/{weight} from queue "{queue}"; staying.',
                    [
                        'count'  => $this->currentMessageCount,
                        'weight' => $this->weights[$this->currentIndex],
                        'queue'  => $this->keys[$this->currentIndex],
                    ]
                );
            }
        } else {
            $this->advance(false);
        }
    }

    /**
     * Advances the iterator to the next queue (or marks the cycle as done when on the last queue)
     * and emits a single consolidated debug log describing both the reason for advancing and its outcome.
     *
     * @param bool $dueToWeight True when advancing because the weight budget was exhausted;
     *                          false when advancing because the current queue was idle.
     */
    private function advance(bool $dueToWeight): void
    {
        $prevQueue = $this->keys[$this->currentIndex];
        $prevWeight = $this->weights[$this->currentIndex];
        $this->currentMessageCount = 0;

        if (isset($this->keys[$this->currentIndex + 1])) {
            $this->currentIndex++;

            if ($dueToWeight) {
                $this->logger->debug(
                    'Queue "{queue}" weight {weight} reached; switching to "{nextQueue}" (weight: {nextWeight}).',
                    [
                        'queue'       => $prevQueue,
                        'weight'      => $prevWeight,
                        'nextQueue'   => $this->keys[$this->currentIndex],
                        'nextWeight'  => $this->weights[$this->currentIndex],
                    ]
                );
            } else {
                $this->logger->debug(
                    'Queue "{queue}" idle; switching to "{nextQueue}" (weight: {nextWeight}).',
                    [
                        'queue'      => $prevQueue,
                        'nextQueue'  => $this->keys[$this->currentIndex],
                        'nextWeight' => $this->weights[$this->currentIndex],
                    ]
                );
            }
        } else {
            $this->isDone = true;

            if ($dueToWeight) {
                $this->logger->debug(
                    'Queue "{queue}" weight {weight} reached; cycle complete.',
                    ['queue' => $prevQueue, 'weight' => $prevWeight]
                );
            } else {
                $this->logger->debug(
                    'Queue "{queue}" idle; cycle complete.',
                    ['queue' => $prevQueue]
                );
            }
        }
    }
}
