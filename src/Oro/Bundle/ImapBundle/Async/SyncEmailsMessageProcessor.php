<?php

namespace Oro\Bundle\ImapBundle\Async;

use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailsTopic;
use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailTopic;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message queue processor that synchronizes multiple emails via IMAP.
 */
class SyncEmailsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private MessageProducerInterface|ImapEmailSynchronizer $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        foreach ($message->getBody()['ids'] as $id) {
            $this->producer->send(SyncEmailTopic::getName(), ['id' => $id]);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [SyncEmailsTopic::getName()];
    }
}
