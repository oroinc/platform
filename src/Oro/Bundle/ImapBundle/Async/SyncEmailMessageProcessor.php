<?php

namespace Oro\Bundle\ImapBundle\Async;

use Oro\Bundle\EmailBundle\Sync\EmailSynchronizerInterface;
use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Message queue processor that synchronizes single email via IMAP.
 */
class SyncEmailMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private EmailSynchronizerInterface $emailSynchronizer;

    public function __construct(EmailSynchronizerInterface $emailSynchronizer)
    {
        $this->emailSynchronizer = $emailSynchronizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $this->emailSynchronizer->syncOrigins([$message->getBody()['id']]);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [SyncEmailTopic::getName()];
    }
}
