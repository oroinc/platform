<?php
namespace Oro\Component\MessageQueue\Client;

interface MessageProducerInterface
{
    /**
     * Sends a message to a topic. There are some message processor may be subscribed to a topic.
     *
     * @param string $topic
     * @param string|array $message The message could be a string or array, in case of array it will be json encoded.
     * @param string $priority Available priorities could be found in MessagePriority class.
     *
     * @return void
     *
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception - if the JMS provider fails to send
     * the message due to some internal error.
     */
    public function send($topic, $message, $priority = MessagePriority::NORMAL);
}
