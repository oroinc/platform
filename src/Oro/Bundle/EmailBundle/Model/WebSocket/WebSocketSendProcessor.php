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
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @param TopicPublisher $publisher
     * @param SecurityContext $securityContext
     */
    public function __construct(
        TopicPublisher $publisher,
        SecurityContext $securityContext
    ) {
        $this->publisher = $publisher;
        $this->securityContext = $securityContext;
    }

    /**
     * Send message into topic
     *
     * @param EmailUser $emailUser
     * @return bool|null
     */
    public function send(EmailUser $emailUser)
    {
        $token = $this->securityContext->getToken();

        if ($token) {
            $messageData = ['email_id' => $emailUser->getEmail()->getId()];
            return $this->publisher->send(
                sprintf(self::TOPIC, $token->getUser()->getId()),
                json_encode($messageData)
            );
        }

        return null;
    }
}
