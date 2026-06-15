<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Creates {@see DefaultQueueIterator} instance for the 'default' consumption mode.
 *
 * @see QueueIteratorFactoryInterface
 */
class DefaultQueueIteratorFactory implements QueueIteratorFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function createQueueIterator(array $boundQueues, string $consumptionMode): \Iterator
    {
        $iterator = new DefaultQueueIterator($boundQueues);
        $iterator->setLogger($this->logger);

        return $iterator;
    }
}
