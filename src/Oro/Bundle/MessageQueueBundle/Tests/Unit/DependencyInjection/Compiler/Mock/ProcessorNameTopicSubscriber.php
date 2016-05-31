<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\ZeroConfig\TopicSubscriber;

class ProcessorNameTopicSubscriber implements TopicSubscriber
{
    public static function getSubscribedTopics()
    {
        return [
            'topic-subscriber-name' => [
                'processorName' => 'subscriber-processor-name'
            ],
        ];
    }
}