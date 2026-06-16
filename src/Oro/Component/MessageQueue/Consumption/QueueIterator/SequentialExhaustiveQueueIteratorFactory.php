<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Creates {@see SequentialExhaustiveQueueIterator} instances for the 'sequential-exhaustive'
 * consumption mode and registers them with a {@see NotifiableQueueIteratorRegistryInterface}.
 */
class SequentialExhaustiveQueueIteratorFactory implements QueueIteratorFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly NotifiableQueueIteratorRegistryInterface $queueIteratorRegistry,
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function createQueueIterator(array $boundQueues, string $consumptionMode): \Iterator
    {
        $queueIterator = new SequentialExhaustiveQueueIterator($boundQueues);
        $queueIterator->setLogger($this->logger);

        $this->queueIteratorRegistry->addQueueIterator($queueIterator);

        return $queueIterator;
    }
}
