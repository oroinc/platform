<?php

namespace Oro\Bundle\EmailBundle\EmailSyncCredentials\NotificationSender;

use Oro\Bundle\EmailBundle\EmailSyncCredentials\NotificationSenderInterface;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

/**
 *  Wrong credential sync email box notification sender channel that uses socket messaging as the channel.
 */
class SocketNotificationSender implements NotificationSenderInterface
{
    /** @var TopicPublisher */
    private $topicPublisher;

    /**
     * @param TopicPublisher $topicPublisher
     */
    public function __construct(TopicPublisher $topicPublisher)
    {
        $this->topicPublisher = $topicPublisher;
    }

    /**
     * {@inheritdoc}
     */
    public function sendNotification(UserEmailOrigin $emailOrigin)
    {
        $originOwner = $emailOrigin->getOwner();
        $topicName = $originOwner ? 'oro/email_sync_fail_u_' . $originOwner->getId() : 'oro/email_sync_fail_system';

        $this->topicPublisher->send($topicName,
            [
                'username' => $emailOrigin->getUser(),
                'host' => $emailOrigin->getImapHost()
            ]
        );
    }
}
