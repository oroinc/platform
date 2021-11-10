<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

class DestinationNameTopicSubscriber implements TopicSubscriberInterface
{
    public static function getSubscribedTopics(): array
    {
        return [
            'subscribed_topic_name' => [
                'destinationName' => 'subscriber_destination_name'
            ],
        ];
    }
}
