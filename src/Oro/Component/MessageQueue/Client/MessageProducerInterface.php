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
     * @param string $body
     * @param string $priority
     */
    public function send($topic, $body, $priority = MessagePriority::NORMAL);
}
