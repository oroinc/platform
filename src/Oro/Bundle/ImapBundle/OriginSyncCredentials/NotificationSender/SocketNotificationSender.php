<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSenderInterface;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;

/**
 *  Wrong credential sync email box notification sender channel that uses socket messaging as the channel.
 */
class SocketNotificationSender implements NotificationSenderInterface
{
    private const TOPIC_IMAP_SYNC_FAIL = 'oro/imap_sync_fail/%s';

    /**
     * @var WebsocketClientInterface
     */
    private $websocketClient;

    /**
     * @var ConnectionChecker
     */
    private $connectionChecker;

    /**
     * @param WebsocketClientInterface $websocketClient
     * @param ConnectionChecker $connectionChecker
     */
    public function __construct(WebsocketClientInterface $websocketClient, ConnectionChecker $connectionChecker)
    {
        $this->websocketClient = $websocketClient;
        $this->connectionChecker = $connectionChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function sendNotification(UserEmailOrigin $emailOrigin)
    {
        if (!$this->connectionChecker->checkConnection()) {
            return;
        }

        $originOwner = $emailOrigin->getOwner();
        $topicUrl = sprintf(self::TOPIC_IMAP_SYNC_FAIL, $originOwner ? $originOwner->getId() : '*');

        $this->websocketClient->publish($topicUrl, [
            'username' => $emailOrigin->getUser(),
            'host' => $emailOrigin->getImapHost(),
        ]);
    }
}
