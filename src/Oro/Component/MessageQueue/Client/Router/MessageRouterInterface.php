<?php

namespace Oro\Component\MessageQueue\Client\Router;

use Oro\Component\MessageQueue\Client\Message;

/**
 * Allows to route message to the correct queue based on topic.
 */
interface MessageRouterInterface
{
    /**
     * @param Message $message
     *
     * @return iterable<Envelope>
     */
    public function handle(Message $message): iterable;
}
