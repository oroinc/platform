<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

/**
 * Registry that manages a set of {@see NotifiableQueueIteratorInterface} instances and broadcasts
 * poll-result notifications ('message received' / 'idle') to all of them.
 *
 * A single instance of this class is shared between a {@see QueueIteratorFactoryInterface}
 * (which calls `addQueueIterator`) and a {@see NotifiableConsumptionExtension}
 * (which calls `notifyMessageReceived`, `notifyIdle`, and `clear`).
 *
 * This class intentionally does NOT implement {@see \Symfony\Contracts\Service\ResetInterface}.
 * Registered iterators must persist for the full lifetime of a single QueueConsumer::consume() call
 * including across the per-message container resets triggered by ContainerResetExtension.
 * The registry is cleared exclusively by {@see NotifiableConsumptionExtension::onInterrupted()}
 * when the consumption session is properly terminated.
 */
class NotifiableQueueIteratorRegistry implements NotifiableQueueIteratorRegistryInterface
{
    /** @var array<NotifiableQueueIteratorInterface> */
    private array $queueIterators = [];

    #[\Override]
    public function addQueueIterator(NotifiableQueueIteratorInterface $iterator): void
    {
        $this->queueIterators[] = $iterator;
    }

    #[\Override]
    public function notifyMessageReceived(): void
    {
        foreach ($this->queueIterators as $iterator) {
            $iterator->notifyMessageReceived();
        }
    }

    #[\Override]
    public function notifyIdle(): void
    {
        foreach ($this->queueIterators as $iterator) {
            $iterator->notifyIdle();
        }
    }

    #[\Override]
    public function clear(): void
    {
        $this->queueIterators = [];
    }
}
