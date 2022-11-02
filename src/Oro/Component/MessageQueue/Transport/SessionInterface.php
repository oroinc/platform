<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * A Session object is a context for producing and consuming messages.
 * It is a factory for its Message Producers and Consumers, creates transport Message
 * and provides a way to create dynamically Queue objects.
 */
interface SessionInterface
{
    /**
     * Creates a transport Message object.
     */
    public function createMessage(
        mixed $body = '',
        array $properties = [],
        array $headers = []
    ): MessageInterface;

    /**
     * Creates a queue identity given a Queue name.
     */
    public function createQueue(string $name): QueueInterface;

    /**
     * Creates a Message consumer for the specified queue.
     */
    public function createConsumer(QueueInterface $queue): MessageConsumerInterface;

    /**
     * Creates a Message producer to send messages to the specified queue.
     */
    public function createProducer(): MessageProducerInterface;

    /**
     * Closes the session.
     */
    public function close(): void;
}
