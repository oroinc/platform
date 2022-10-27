<?php

namespace Oro\Component\MessageQueue\Test\Async;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Basic message processor for test purposes.
 */
class BasicMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public const TEST_TOPIC = 'oro.message_queue.basic_message_processor';

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
        return [self::TEST_TOPIC];
    }
}
