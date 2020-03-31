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
     *
     * @param string $body
     * @param array $properties
     * @param array $headers
     *
     * @return MessageInterface
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): MessageInterface;

    /**
     * Creates a queue identity given a Queue name.
     *
     * @param string $name
     *
     * @return QueueInterface
     */
    public function createQueue(string $name): QueueInterface;

    /**
     * Creates a Message consumer for the specified queue.
     *
     * @param QueueInterface $queue
     *
     * @return MessageConsumerInterface
     */
    public function createConsumer(QueueInterface $queue): MessageConsumerInterface;

    /**
     * Creates a Message producer to send messages to the specified queue.
     *
     * @return MessageProducerInterface
     */
    public function createProducer(): MessageProducerInterface;

    /**
     * Closes the session.
     *
     * @return void
     */
    public function close(): void;
}
