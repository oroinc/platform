<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * A client uses a MessageProducer object to send messages to a queue.
 */
interface MessageProducerInterface
{
    /**
     * Sends a message to a queue for an unidentified message producer.
     */
    public function send(QueueInterface $queue, MessageInterface $message): void;
}
