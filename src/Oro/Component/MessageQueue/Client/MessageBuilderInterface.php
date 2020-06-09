<?php

namespace Oro\Component\MessageQueue\Client;

/**
 * Represents a builder for a message to be sent to the queue by a message producer.
 * An instance of a builder can be passed to the send() method of the message producer instead of the message.
 * Message builders can be helpful when it is required to postpone creation of a message,
 * e.g. when a message should contains ID of a new entity, but at the time the send() method
 * is called the ID is not generated yet.
 * @see \Oro\Component\MessageQueue\Client\MessageProducerInterface::send
 */
interface MessageBuilderInterface
{
    /**
     * Returns a built message.
     *
     * @return string|array|Message
     */
    public function getMessage();
}
