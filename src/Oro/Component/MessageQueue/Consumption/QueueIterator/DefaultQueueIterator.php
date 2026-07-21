<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Iterates over bound queues in a fixed default order.
 *
 * Consumption schema:
 *   - 1 queue:  Q1(1)
 *   - 2 queues: Q1(1), Q2(1)
 *   - 3 queues: Q1(1), Q2(1), Q3(1)
 *   - 4 queues: Q1(1), Q2(1), Q3(1), Q4(1)
 *   - 5 queues: Q1(1), Q2(1), Q3(1), Q4(1), Q5(1)
 *   - ... and so on.
 * Each "1" means exactly one poll/consume of that queue before advancing to the next.
 */
class DefaultQueueIterator implements QueueIteratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string NAME = 'default';

    /**
     * @var array<string>
     */
    private array $keys;

    /**
     * @var array<array<string,string>>
     */
    private array $values;

    private int $position = 0;

    /**
     * @param array<string,array<string,string>> $boundQueues Map of queue name => queue settings array.
     */
    public function __construct(array $boundQueues)
    {
        $this->keys = array_keys($boundQueues);
        $this->values = array_values($boundQueues);

        $this->logger = new NullLogger();
    }

    #[\Override]
    public function current(): array
    {
        return $this->values[$this->position];
    }

    #[\Override]
    public function key(): string
    {
        return $this->keys[$this->position];
    }

    #[\Override]
    public function next(): void
    {
        ++$this->position;

        if ($this->valid()) {
            $this->logger->debug('Switching to queue "{queue}".', [
                'queue' => $this->keys[$this->position],
            ]);
        } elseif (count($this->keys) > 1) {
            $this->logger->debug('All queues visited - default cycle complete.');
        }
    }

    #[\Override]
    public function rewind(): void
    {
        $this->position = 0;

        if ($this->keys !== []) {
            $this->logger->debug(
                'Starting a new default cycle; first queue: "{queue}".',
                ['queue' => $this->keys[0]]
            );
        }
    }

    #[\Override]
    public function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }
}
