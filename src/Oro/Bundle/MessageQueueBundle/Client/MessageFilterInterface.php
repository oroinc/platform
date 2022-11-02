<?php

namespace Oro\Bundle\MessageQueueBundle\Client;

/**
 * Represents a class that can be used to filter messages before they are sent to the queue.
 * For example, filters can be used to remove duplicated messaged, combine several messages in one message, etc.
 */
interface MessageFilterInterface
{
    public function apply(MessageBuffer $buffer): void;
}
