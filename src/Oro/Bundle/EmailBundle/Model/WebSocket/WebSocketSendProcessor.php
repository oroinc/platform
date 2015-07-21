<?php

namespace Oro\Bundle\EmailBundle\Model\WebSocket;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\UserBundle\Entity\User;

class WebSocketSendProcessor
{
    const TOPIC = 'oro/email_event/user_%s';

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
     * @param array $usersWithNewEmails
     */
    public function send($usersWithNewEmails)
    {
        if ($usersWithNewEmails) {
            foreach ($usersWithNewEmails as $item) {
                $user = $item['owner'];
                $messageData = [[
                    'new_email' => true,
                    'count_new' => isset($item['new']) && $item['new']>0 ? : 0
                ]];

                $this->publisher->send(self::getUserTopic($user), json_encode($messageData));
            }
        }
    }
}
