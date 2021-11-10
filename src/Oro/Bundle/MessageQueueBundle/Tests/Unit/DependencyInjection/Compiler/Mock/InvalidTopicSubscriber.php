<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

class InvalidTopicSubscriber implements TopicSubscriberInterface
{
    public static function getSubscribedTopics(): array
    {
        return [12345];
    }
}
