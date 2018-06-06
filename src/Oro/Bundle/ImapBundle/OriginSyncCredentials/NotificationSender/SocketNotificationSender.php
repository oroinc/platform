<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSenderInterface;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;

/**
 *  Wrong credential sync email box notification sender channel that uses socket messaging as the channel.
 */
class SocketNotificationSender implements NotificationSenderInterface
{
    /**
     * @var WebsocketClientInterface
     */
    private $websocketClient;

    /**
     * @param WebsocketClientInterface $websocketClient
     */
    public function __construct(WebsocketClientInterface $websocketClient)
    {
        $this->websocketClient = $websocketClient;
    }

    /**
     * {@inheritdoc}
     */
    public function sendNotification(UserEmailOrigin $emailOrigin)
    {
        $originOwner = $emailOrigin->getOwner();
        $topicName = $originOwner ? 'oro/imap_sync_fail_u_' . $originOwner->getId() : 'oro/imap_sync_fail_system';

        $this->websocketClient->publish(
            $topicName,
            ['username' => $emailOrigin->getUser(), 'host' => $emailOrigin->getImapHost()]
        );
    }
}
