<?php

namespace Oro\Component\MessageQueue\Client;

/**
 * An interface for creates unique job automatically before sending the message
 */
interface MessageProducerMiddlewareInterface
{
    public function handle(Message $message): void;
}
