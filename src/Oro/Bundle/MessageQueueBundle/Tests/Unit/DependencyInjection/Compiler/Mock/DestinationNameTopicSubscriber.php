<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

class DestinationNameTopicSubscriber implements TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [
            'topic-subscriber-name' => [
                'destinationName' => 'subscriber-destination-name'
            ],
        ];
    }
}
