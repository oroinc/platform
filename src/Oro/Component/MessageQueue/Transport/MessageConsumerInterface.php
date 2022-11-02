<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * A client uses a MessageConsumer object to receive messages from a queue.
 */
interface MessageConsumerInterface
{
    /**
     * Receives the next message that arrives within the specified timeout interval.
     * This call blocks until a message arrives, the timeout expires, or this message consumer is closed.
     * A timeout of zero never expires, and the call blocks indefinitely.
     *
     * @param int $timeout the timeout value (in seconds)
     *
     * @return MessageInterface|null
     */
    public function receive($timeout = 0): ?MessageInterface;

    /**
     * Tell the MQ broker that the message was processed successfully
     */
    public function acknowledge(MessageInterface $message): void;

    /**
     * Tell the MQ broker that the message was rejected
     *
     * @param MessageInterface $message
     * @param bool $requeue
     *
     * @return void
     */
    public function reject(MessageInterface $message, $requeue = false): void;
}
