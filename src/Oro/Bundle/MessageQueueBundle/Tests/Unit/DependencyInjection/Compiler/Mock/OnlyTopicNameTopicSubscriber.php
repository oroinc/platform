<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

class OnlyTopicNameTopicSubscriber implements TopicSubscriberInterface
{
    public static function getSubscribedTopics(): array
    {
        return ['subscribed_topic_name'];
    }
}
