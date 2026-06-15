<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\QueueIterator;

/**
 * Extended queue iterator interface that exposes notification callbacks
 * so the consumption extension can inform the iterator whether the last
 * queue poll produced a message or was idle.
 */
interface NotifiableQueueIteratorInterface extends QueueIteratorInterface
{
    /**
     * Called by the consumption extension after a message has been successfully received
     * and processed from the currently active queue.
     */
    public function notifyMessageReceived(): void;

    /**
     * Called by the consumption extension when the currently active queue
     * returned no message (idle poll).
     */
    public function notifyIdle(): void;
}
