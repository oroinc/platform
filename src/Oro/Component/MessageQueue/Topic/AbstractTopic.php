<?php

namespace Oro\Component\MessageQueue\Topic;

use Oro\Component\MessageQueue\Client\MessagePriority;

/**
 * Basic implementation of {@see TopicInterface}.
 */
abstract class AbstractTopic implements TopicInterface
{
    public function getDefaultPriority(string $queueName): string
    {
        return MessagePriority::NORMAL;
    }
}
