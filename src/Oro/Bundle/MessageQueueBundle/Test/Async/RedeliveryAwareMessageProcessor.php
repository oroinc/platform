<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Async;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\RedeliveryMessageExtension;
use Oro\Bundle\MessageQueueBundle\Test\Async\Topic\SampleNormalizableBodyTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message processor that intentionally requeues a message when it comes for the 1st time.
 */
class RedeliveryAwareMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private static array $processedMessages = [];

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        if ((int)$message->getProperty(RedeliveryMessageExtension::PROPERTY_REDELIVER_COUNT, '0') < 1) {
            self::$processedMessages[] = [
                'body' => $message->getBody(),
                'status' => MessageProcessorInterface::REQUEUE,
            ];

            return MessageProcessorInterface::REQUEUE;
        }

        self::$processedMessages[] = [
            'body' => $message->getBody(),
            'status' => MessageProcessorInterface::ACK,
        ];

        return MessageProcessorInterface::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [SampleNormalizableBodyTopic::getName()];
    }

    public static function getProcessedMessages(): array
    {
        return self::$processedMessages;
    }

    public static function clearProcessedMessages(): void
    {
        self::$processedMessages = [];
    }
}
