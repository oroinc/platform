<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * A Queue object encapsulates a provider-specific queue name.
 * It is the way a client specifies the identity of a queue to transport methods.
 */
interface QueueInterface
{
    /**
     * Gets the name of this queue.
     */
    public function getQueueName(): string;
}
