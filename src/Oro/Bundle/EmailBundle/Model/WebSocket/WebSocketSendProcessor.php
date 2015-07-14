<?php

namespace Oro\Bundle\EmailBundle\Model\WebSocket;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\UserBundle\Entity\User;

class WebSocketSendProcessor
{
    const TOPIC = 'oro/email/user_%s';

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
     * @return string
     */
    public static function getUserTopic(User $user)
    {
        return sprintf(self::TOPIC, $user->getId());
    }

    /**
     * Send message into topic
     *
     * @param EmailUser $emailUser
     * @return bool|null
     */
    public function send(EmailUser $emailUser)
    {
        if ($emailUser->getOwner()) {
            $messageData = ['email_id' => $emailUser->getEmail()->getId()];
            return $this->publisher->send(
                self::getUserTopic($emailUser->getOwner()),
                json_encode($messageData)
            );
        }

        return null;
    }
}
