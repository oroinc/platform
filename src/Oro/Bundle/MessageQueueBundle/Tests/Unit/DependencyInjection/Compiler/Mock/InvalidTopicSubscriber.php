<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\ZeroConfig\TopicSubscriber;

class InvalidTopicSubscriber implements TopicSubscriber
{
    public static function getSubscribedTopics()
    {
        return [12345];
    }
}
