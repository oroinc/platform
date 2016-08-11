<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\MessageInterface;

interface MessageProducerInterface
{
    /**
     * The message is used internally by send method to create a transport message.
     * It may be useful when you have to customize message, for example set some extra headers.
     *
     * @param string|array $body The message could be a string or array, in case of array it will be encoded to json.
     *
     * @return MessageInterface
     */
    public function createMessage($body);

    /**
     * Sends a message to a topic. There are some message processor may be subscribed to a topic.
     *
     * @param string $topic
     * @param string|array|MessageInterface $message
     * @param string $priority Available priorities could be found in MessagePriority class.
     *
     * @return void
     *
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception - if the producer fails to send
     * the message due to some internal error.
     */
    public function send($topic, $message, $priority = MessagePriority::NORMAL);
}
