<?php

namespace Oro\Bundle\EmailBundle\Model\WebSocket;

use Symfony\Component\Security\Core\SecurityContext;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

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
    public function __construct(
        TopicPublisher $publisher
    ) {
        $this->publisher = $publisher;
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
                sprintf(self::TOPIC, $emailUser->getOwner()->getId()),
                json_encode($messageData)
            );
        }

        return null;
    }
}
