<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

/**
 * Contract for registries that manage a set of {@see NotifiableQueueIteratorInterface} instances
 * and broadcast poll-result notifications to all of them.
 */
interface NotifiableQueueIteratorRegistryInterface
{
    /**
     * Registers an iterator so it will receive poll-result notifications.
     */
    public function addQueueIterator(NotifiableQueueIteratorInterface $iterator): void;

    /**
     * Broadcasts a 'message received' signal to all registered iterators.
     */
    public function notifyMessageReceived(): void;

    /**
     * Broadcasts an 'idle' signal to all registered iterators.
     */
    public function notifyIdle(): void;

    /**
     * Removes all registered iterators from the registry.
     */
    public function clear(): void;
}
