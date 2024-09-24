<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

class UnknownTopicSubscriber implements TopicSubscriberInterface
{
    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return ['unknown_topic'];
    }
}
