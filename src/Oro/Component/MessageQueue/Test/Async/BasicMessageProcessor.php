<?php

namespace Oro\Component\MessageQueue\Test\Async;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\Async\Topic\BasicMessageTestTopic;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Basic message processor for test purposes.
 */
class BasicMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [BasicMessageTestTopic::getName()];
    }
}
