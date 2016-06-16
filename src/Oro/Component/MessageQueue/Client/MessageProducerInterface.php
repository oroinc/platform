<?php
namespace Oro\Component\MessageQueue\Client;

interface MessageProducerInterface
{
    /**
     * Sends a message to a topic channel.
     * Body could be a string or array, in case of array it will be json encoded.
     * For possible priority values @see MessagePriority constants
     *
     * @param string $topic
     * @param string|array $message
     * @param string $priority
     */
    public function send($topic, $message, $priority = MessagePriority::NORMAL);
}
