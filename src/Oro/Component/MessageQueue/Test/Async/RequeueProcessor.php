<?php

namespace Oro\Component\MessageQueue\Test\Async;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\Async\Topic\RequeueTestTopic;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Requeue message processor.
 */
class RequeueProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        return self::REQUEUE;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [RequeueTestTopic::getName()];
    }
}
