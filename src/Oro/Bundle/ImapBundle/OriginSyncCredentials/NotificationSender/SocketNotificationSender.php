<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSenderInterface;
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
        $topicName = $originOwner ? 'oro/imap_sync_fail_u_' . $originOwner->getId() : 'oro/imap_sync_fail_system';

        $this->topicPublisher->send(
            $topicName,
            [
                'username' => $emailOrigin->getUser(),
                'host' => $emailOrigin->getImapHost()
            ]
        );
    }
}
