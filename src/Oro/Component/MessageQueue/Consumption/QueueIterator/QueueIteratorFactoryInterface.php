<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

/**
 * Interface for factories that create queue iterators for a specific consumption mode.
 */
interface QueueIteratorFactoryInterface
{
    /**
     * Creates a queue iterator for the given bound queues.
     *
     * @param array<string,array<string,string>> $boundQueues Map of queue name => queue settings array.
     * @param string $consumptionMode The consumption mode for which the iterator should be created.
     *
     * @return \Iterator A queue iterator that iterates over the given bound queues according
     *  to the specified consumption mode.
     */
    public function createQueueIterator(array $boundQueues, string $consumptionMode): \Iterator;
}
