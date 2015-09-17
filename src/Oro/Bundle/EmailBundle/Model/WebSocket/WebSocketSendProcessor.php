<?php

namespace Oro\Bundle\EmailBundle\Model\WebSocket;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\UserBundle\Entity\User;

class WebSocketSendProcessor
{
    const TOPIC = 'oro/email_event/user_%s_org_%s';

    /**
     * @var TopicPublisher
     */
    protected $publisher;

    /**
     * @param TopicPublisher $publisher
     */
    public function __construct(TopicPublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Get user topic
     *
     * @param User|int $user
     * @param Organization $organization
     * @return string
     */
    public static function getUserTopic($user, Organization $organization)
    {
        $userId = $user instanceof User ? $user->getId() : $user;

        return sprintf(self::TOPIC, $userId, $organization->getId());
    }

    /**
     * Send message into topic
     *
     * @param array $usersWithNewEmails
     */
    public function send($usersWithNewEmails)
    {
        if ($usersWithNewEmails) {
            foreach ($usersWithNewEmails as $ownerId => $item) {
                /** @var EmailUser $emailUser */
                $emailUser = $item['entity'];

                $topic = self::getUserTopic($ownerId, $emailUser->getOrganization());
                $messageData = [
                    'hasNewEmail' => array_key_exists('new', $item) === true && $item['new'] > 0 ? : false
                ];

                $this->publisher->send($topic, json_encode($messageData));
            }
        }
    }
}
