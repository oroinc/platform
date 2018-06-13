<?php

namespace Oro\Bundle\EmailBundle\Model\WebSocket;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sends messages about new emails to websocket server.
 */
class WebSocketSendProcessor
{
    const TOPIC = 'oro/email_event/%s/%s';

    /**
     * @var WebsocketClientInterface
     */
    protected $websocketClient;

    /**
     * @var ConnectionChecker
     */
    protected $connectionChecker;

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
     * Get user topic
     *
     * @param User|int $user
     * @param Organization $organization
     * @return string
     */
    public static function getUserTopic($user, Organization $organization = null)
    {
        return sprintf(
            self::TOPIC,
            $user instanceof User ? $user->getId() : $user,
            $organization ? $organization->getId() : '*'
        );
    }

    /**
     * Send message into topic
     *
     * @param array $usersWithNewEmails
     */
    public function send($usersWithNewEmails)
    {
        if ($usersWithNewEmails && $this->connectionChecker->checkConnection()) {
            foreach ($usersWithNewEmails as $ownerId => $item) {
                /** @var EmailUser $emailUser */
                $emailUser = $item['entity'];

                $topic = self::getUserTopic($ownerId, $emailUser->getOrganization());
                $messageData = [
                    'hasNewEmail' => array_key_exists('new', $item) === true && $item['new'] > 0 ? : false
                ];

                $this->websocketClient->publish($topic, $messageData);
            }
        }
    }
}
