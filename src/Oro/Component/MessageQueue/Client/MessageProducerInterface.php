<?php

namespace Oro\Component\MessageQueue\Client;

/**
 * Represents a class that is used to send messages to the queue.
 */
interface MessageProducerInterface
{
    /**
     * Sends a message to a topic. There are some message processor may be subscribed to a topic.
     *
     * @param string $topic
     * @param string|array|Message|MessageBuilderInterface $message
     *
     * @return void
     *
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception - if the producer fails to send
     * the message due to some internal error.
     */
    public function send($topic, $message);
}
