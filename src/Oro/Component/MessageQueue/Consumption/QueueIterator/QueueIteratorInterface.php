<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

/**
 * Interface for queue iterators that iterate over bound queues during message consumption.
 *
 * Each iteration step exposes one bound queue:
 *   - {@see \Iterator::key()} yields the queue name (string).
 *   - {@see \Iterator::current()} yields the queue settings (array).
 *
 * Concrete implementations determine the order in which queues are visited
 * (e.g. weighted-round-robin, strict-priority-interleaving).
 *
 * @extends \Iterator<string, array>
 */
interface QueueIteratorInterface extends \Iterator
{
}
