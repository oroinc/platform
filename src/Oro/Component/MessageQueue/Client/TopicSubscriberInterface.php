<?php

namespace Oro\Component\MessageQueue\Client;

/**
 * An interface for message queue processors that can subscribe to topics on their own.
 */
interface TopicSubscriberInterface
{
    /**
     * @return array
     *  [
     *      'topicName1',
     *      'topicName2' => [],
     *       // destinationName are optional
     *      'topicName3' => ['destinationName' => 'sample_destination']
     *  ]
     */
    public static function getSubscribedTopics();
}
