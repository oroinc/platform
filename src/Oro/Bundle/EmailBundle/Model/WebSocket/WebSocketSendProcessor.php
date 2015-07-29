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
     * @param User $user
     * @param Organization $organization
     * @return string
     */
    public static function getUserTopic(User $user, Organization $organization)
    {
        return sprintf(self::TOPIC, $user->getId(), $organization->getId());
    }

    /**
     * Send message into topic
     *
     * @param array $usersWithNewEmails
     */
    public function send($usersWithNewEmails)
    {
        if ($usersWithNewEmails) {
            foreach ($usersWithNewEmails as $item) {
                /** @var EmailUser $emailUser */
                $emailUser = $item['entity'];
                $messageData = [
                    'hasNewEmail' => array_key_exists('new', $item) === true && $item['new'] > 0 ? : false
                ];

                $this->publisher->send(
                    self::getUserTopic(
                        $emailUser->getOwner(),
                        $emailUser->getOrganization()
                    ),
                    json_encode($messageData)
                );
            }
        }
    }
}
