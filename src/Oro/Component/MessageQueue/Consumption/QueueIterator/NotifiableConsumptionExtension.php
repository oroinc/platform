<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Consumption extension that bridges MQ consumption events to a
 * {@see NotifiableQueueIteratorRegistryInterface}.
 *
 * On every successful message receipt it signals 'message received' to keep the active iterator
 * pointed at the high-priority queue. On every idle poll it signals 'idle' so the iterator
 * advances according to its state machine. On interruption it clears the registry to
 * release stale iterator references before the next consumption session.
 */
class NotifiableConsumptionExtension extends AbstractExtension
{
    public function __construct(
        private readonly NotifiableQueueIteratorRegistryInterface $queueIteratorRegistry,
    ) {
    }

    /**
     * Signals 'message received' to the registry after a message has been processed.
     */
    #[\Override]
    public function onPostReceived(Context $context): void
    {
        $this->queueIteratorRegistry->notifyMessageReceived();
    }

    /**
     * Signals 'idle' to the registry when a queue poll returned no message.
     */
    #[\Override]
    public function onIdle(Context $context): void
    {
        $this->queueIteratorRegistry->notifyIdle();
    }

    /**
     * Clears the registry when consumption is interrupted to release stale iterator references.
     */
    #[\Override]
    public function onInterrupted(Context $context): void
    {
        $this->queueIteratorRegistry->clear();
    }
}
