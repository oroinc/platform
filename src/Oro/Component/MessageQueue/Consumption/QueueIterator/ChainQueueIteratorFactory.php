<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Selects the correct {@see QueueIteratorFactoryInterface} from the {@see QueueIteratorFactoryRegistry}
 * and delegates queue iterator creation to it.
 */
class ChainQueueIteratorFactory implements QueueIteratorFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly QueueIteratorFactoryRegistry $queueIteratorFactoryRegistry,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritDoc}
     *
     * Creates a queue iterator for the given bound queues, using the factory registered for
     * the requested consumption mode.
     *
     * @throws \LogicException When no factory supports the requested consumption mode.
     */
    #[\Override]
    public function createQueueIterator(array $boundQueues, string $consumptionMode): \Iterator
    {
        $this->logger->info('Creating a queue iterator in "{consumptionMode}" mode for queues: {queues}', [
            'consumptionMode' => $consumptionMode,
            'queues' => implode(', ', array_keys($boundQueues)),
            'queuesSettings' => $boundQueues,
        ]);

        return $this->queueIteratorFactoryRegistry
            ->getQueueIteratorFactory($consumptionMode)
            ->createQueueIterator($boundQueues, $consumptionMode);
    }
}
